<?php

function GetUserAnnouncements($user_ids)
{

//    $sql = "select au.user_id, a.announcement_id, title, a.\"text\", at.\"type\"
//            from announcement_audience au, announcements a, announcement_type at, students s
//            where au.announcement_id = a.announcement_id
//            and au.user_id = s.student_id
//            and a.announcement_type_id = at.announcement_type_id
//            and s.user_id in ($user_ids)";

    $sql = "select au.user_id, a.announcement_id, title, a.\"text\", at.\"type\"
            from announcements a, announcement_audience au, user_fcm_tokens uft, announcement_type at
            where a.announcement_id = au.announcement_id
            and au.user_id = uft.user_id
            and uft.is_student = true
            and a.announcement_type_id = at.announcement_type_id
            and au.user_id in ($user_ids)";

    $data = DBGet($sql, array(), array());

    return $data;

}