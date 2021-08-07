<?php

function UpdateFCMToken($user_id, $token, $is_student)
{

    $sql = "delete from user_fcm_tokens where user_id = $user_id and is_student = $is_student";

    if (DBQuery($sql)) {

        $sql = "insert into user_fcm_tokens values($user_id, '$token', $is_student)";

        return DBQuery($sql);

    }

    return false;

}

function GetAllFCMTokens()
{

    $sql = "select * from user_fcm_tokens";

    return DBGet($sql);

}

function GetAllUnsentNotifications($limit)
{

    $sql = "select a.announcement_id, title, a.\"text\" as message, au.user_id, uft.token
            from announcement_audience au, announcements a, user_fcm_tokens uft
            where au.is_sent = false
            and au.announcement_id = a.announcement_id
            and au.user_id = uft.user_id
            order by announcement_id limit " . $limit;

    return DBGet($sql);

}

function MarkSent($announcement_id, $user_id)
{

    $sql = "update announcement_audience
            set is_sent = true
            where user_id = $user_id
            and announcement_id = $announcement_id";

    DBQuery($sql);

}