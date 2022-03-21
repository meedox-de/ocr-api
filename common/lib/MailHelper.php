<?php

namespace common\lib;

class MailHelper
{
    /**
     * sends mail
     * @param string $receiver
     * @param string $subject
     * @param string $content
     *
     * @return void
     */
    public static function send(string $receiver, string $subject, string $content) :void
    {
        mail( $receiver, $subject, $content, EMAIL_SENDER );
    }
}