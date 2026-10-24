<?php
/*
 * Single destination for every "Book Now" button.
 *  - patients go straight to the list of sessions (optionally filtered);
 *  - visitors are asked to log in or register and are then brought back;
 *  - doctors and administrators are sent to their dashboard with an explanation.
 */
require __DIR__ . '/includes/bootstrap.php';

$query = array_filter([
    'specialty' => query_int('specialty'),
    'doctor' => query_int('doctor'),
]);
$target = 'patient/schedule.php' . ($query ? '?' . http_build_query($query) : '');

$user = current_user();
if ($user && $user['role'] === 'p') {
    header('Location: ' . url($target), true, 303);
    exit;
}
if ($user) {
    flash('info', 'Booking appointments is done from a patient account. You are signed in as ' . strtolower(role_info($user['role'])['label']) . '.');
    redirect(role_info($user['role'])['home']);
}

flash('info', 'Log in to book an appointment. New to Uhai Bora? Register for a patient account first.');
redirect('login.php', ['next' => $target]);
