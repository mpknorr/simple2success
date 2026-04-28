<?php
session_start();
if (empty($_SESSION['userid'])) { http_response_code(403); exit(); }
require_once __DIR__ . '/conn.php';

$leadId  = (int)$_SESSION['userid'];
$allowed = ['video_play','video_25','video_50','video_75','video_complete',
            'video2_play','video3_play','video4_play'];
$event   = in_array($_POST['event'] ?? '', $allowed, true)
           ? $_POST['event'] : 'video_play';
$video   = mysqli_real_escape_string($link, substr($_POST['video'] ?? '', 0, 150));
$page    = mysqli_real_escape_string($link, substr($_POST['page']  ?? '', 0, 100));
$ip      = mysqli_real_escape_string($link, $_SERVER['REMOTE_ADDR'] ?? '');

mysqli_query($link, "INSERT INTO lead_events (lead_id, event_type, page, meta, ip)
    VALUES ($leadId, '$event', '$page', '$video', '$ip')");

http_response_code(200);
