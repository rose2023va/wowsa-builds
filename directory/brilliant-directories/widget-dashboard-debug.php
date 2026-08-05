<?php
// TEMPORARY DEBUG — remove after confirming field names
if (!user::isUserLogged($_COOKIE)) {
    echo '<p style="color:red">NOT LOGGED IN</p>';
    return;
}

$member = getUser($_COOKIE['userid'], $w);

echo '<pre style="font-size:11px;background:#f5f5f5;padding:12px;border-radius:6px;overflow:auto">';
echo 'Cookie userid: ' . $_COOKIE['userid'] . "\n\n";
echo 'subscription_id: ' . $member['subscription_id'] . "\n";
echo 'id: ' . $member['id'] . "\n";
echo 'user_photo: ' . $member['user_photo'] . "\n";
echo 'post_content (first 80 chars): ' . substr($member['post_content'], 0, 80) . "\n";
echo '</pre>';
?>
