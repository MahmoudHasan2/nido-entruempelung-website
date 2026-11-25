<?php
declare(strict_types=1);

/**
 * Admin dashboard front controller (/admin/*).
 * Every route except /admin/login requires an authenticated session; every POST requires a CSRF token.
 */

require_once APP . '/admin/ui.php';
require_once APP . '/admin/ctl_core.php';
require_once APP . '/admin/ctl_content.php';
require_once APP . '/admin/ctl_inbox.php';

send_security_headers(true);
session_boot();

$sub = trim(substr($path, strlen('/admin')), '/');   // '' | login | services | services/edit …
$isPost = is_post();

/* ---- public admin routes ---- */
if ($sub === 'login') {
    ctl_login();
    exit;
}

/* ---- everything else needs a session ---- */
$user = admin_user();
if (!$user) {
    redirect('/admin/login');
}

if ($isPost && !csrf_check()) {
    if (str_starts_with($sub, 'media/upload') || $sub === 'reorder') {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Sitzung abgelaufen. Bitte Seite neu laden.']);
        exit;
    }
    flash('Die Sitzung ist abgelaufen oder der Sicherheits-Token war ungültig. Bitte versuchen Sie es erneut.', 'err');
    redirect('/admin');
}

/* ---- forced password change on first login ---- */
if ((int) $user['must_change'] === 1 && $sub !== 'password' && $sub !== 'logout') {
    redirect('/admin/password');
}

switch (true) {
    case $sub === '':
        ctl_dashboard();
        break;
    case $sub === 'logout':
        if ($isPost) {
            admin_logout();
            redirect('/admin/login');
        }
        redirect('/admin');
    case $sub === 'password':
        ctl_password($user);
        break;
    case $sub === 'settings':
        ctl_settings();
        break;
    case $sub === 'services':
        ctl_services();
        break;
    case $sub === 'services/edit':
        ctl_service_edit();
        break;
    case $sub === 'gallery':
        ctl_gallery();
        break;
    case $sub === 'testimonials':
        ctl_testimonials();
        break;
    case $sub === 'media':
        ctl_media();
        break;
    case $sub === 'media/json':
        ctl_media_json();
        break;
    case $sub === 'media/upload':
        ctl_media_upload();
        break;
    case $sub === 'inquiries':
        ctl_inquiries();
        break;
    case $sub === 'seo':
        ctl_seo();
        break;
    case $sub === 'navigation':
        ctl_navigation();
        break;
    case $sub === 'reorder':
        ctl_reorder();
        break;
    default:
        http_response_code(404);
        admin_page('Nicht gefunden', '', function () {
            echo '<div class="empty"><p>Diese Seite gibt es im Dashboard nicht.</p><a class="btn btn-primary" href="/admin">Zur Übersicht</a></div>';
        });
}
