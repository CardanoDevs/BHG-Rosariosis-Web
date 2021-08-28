<?php

function GetUserAnnouncements($user_id, $is_student)
{
    $sql = "select au.user_id, a.announcement_id, title, a.\"text\", at.\"type\"
        from announcement_audience au, announcements a, user_fcm_tokens uft, announcement_type at
        where au.announcement_id = a.announcement_id
        and au.is_student = ".$is_student." 
        and au.user_id = uft.user_id
        and a.announcement_type_id = at.announcement_type_id
        and au.user_id = ".$user_id;
    // $sql = "select au.user_id, a.announcement_id, title, a.\"text\", at.\"type\"
    //         from announcements a, announcement_audience au, user_fcm_tokens uft, announcement_type at
    //         where a.announcement_id = au.announcement_id
    //         and au.user_id = uft.user_id
    //         and a.announcement_type_id = at.announcement_type_id
    //         and au.user_id in ($user_ids)";
    $data = DBGet($sql, array(), array());
    return $data;
}