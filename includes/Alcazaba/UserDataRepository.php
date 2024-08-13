<?php

class UserDataRepository
{
    public function getAllUserSignUpData(): array
    {
        global $wpdb;

        $sql = <<<EOF
SELECT user_registered, user_email FROM `wp_users` WHERE user_activation_key = ''; 
EOF;

        return $wpdb->get_results($sql);
    }
}
