<?php
/**
 * @author 345874331@qq.com
 * @version 1.0.0
 */

namespace app\libraries\log;

use yii\log\Logger;
use yii\helpers\ArrayHelper;

class LogstashFileTarget extends \yii\log\FileTarget
{
    /** @var bool Whether to log a message containing the current user name and ID. */
    public $logUser = true;
    /** @var array Add more context */
    public $context = [];

    /**
     * Processes the given log messages.
     *
     * @param array $messages Log messages to be processed.
     * @param bool  $final    Whether this method is called at the end of the current application
     */
    public function collect($messages, $final)
    {
        $this->messages = array_merge(
            $this->messages,
            $this->filterMessages($messages, $this->getLevels(), $this->categories, $this->except)
        );
        $count = count($this->messages);
        if (($count > 0) && (($final == true) || ($this->exportInterval > 0) && ($count >= $this->exportInterval))) {
            $this->addContextToMessages();
            $this->export();
            $this->messages = [];
        }
    }
    /**
     * Formats a log message.
     *
     * @param array $message The log message to be formatted.
     *
     * @return string
     */
    public function formatMessage($message)
    {
        return json_encode($this->prepareMessage($message));
    }
    /**
     * Updates all messages if there are context variables.
     */
    protected function addContextToMessages()
    {
        $context = $this->getContextMessage();
        if ($context === []) {
            return;
        }
        foreach ($this->messages as &$message) {
            $message[0] = ArrayHelper::merge($context, $this->parseText($message[0]));
        }
    }
    /**
     * Generates the context information to be logged.
     *
     * @return array
     */
    protected function getContextMessage()
    {
        $context = $this->context;
        if (($this->logUser === true) && ($user = \Yii::$app->get('user', false)) !== null) {
            /** @var \yii\web\User $user */
            $user = $user->identity;
            $context['userId'] = $user ? $user->user_id : null;
        }
        $context['raw'] = file_get_contents('php://input');

        if(PHP_SAPI == 'cli') {
            $context['post'] = $context['get'] = [];
        } else {
            $context['post'] = \Yii::$app->request->getBodyParams();
            $context['get'] = \Yii::$app->request->getQueryParams();
        }

        $serverVars = [
            'HTTP_CONTENT_TYPE',
            'HTTP_USER_AGENT',
            'REQUEST_URI',
            'REQUEST_TIME_FLOAT',
        ];

        foreach ($serverVars as $var) {
            $context[strtolower($var)] = isset($_SERVER[$var]) ? $_SERVER[$var] : '';
        }

        return $context;
    }
    /**
     * Convert's any type of log message to array.
     *
     * @param mixed $text Input log message.
     *
     * @return array
     */
    protected function parseText($text)
    {
        $type = gettype($text);

        switch ($type) {
            case 'string':
                return ['@message' => $text];
                break;
            case 'array':
                return $text;
                break;
            case 'object':
                if ($text instanceof \Throwable || $text instanceof \Exception) {
                    return [
                        '@message' => $text->getMessage(),
                        'trace' => "file:{$text->getFile()}, line:{$text->getLine()}"
                    ];
                } else {
                    return ['@message' => get_object_vars($text)];
                }
                break;
            default:
                return ['@message' => \Yii::t('log', "Warning, wrong log message type '{$type}'")];
                break;

        }
    }
    /**
     * Transform log message to assoc.
     *
     * @param array $message The log message.
     *
     * @return array
     */
    protected function prepareMessage($message)
    {
        list($text, $level, $category, $timestamp) = $message;

        $level = Logger::getLevelName($level);
        $timestamp = date('c', $timestamp);
        $result = ArrayHelper::merge(
            $this->parseText($text),
            ['level' => $level, 'category' => $category, '@timestamp' => $timestamp]
        );

        return $result;
    }
}
