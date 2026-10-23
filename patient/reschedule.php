<?php
session_start();

if (($_SESSION['usertype'] ?? '') !== 'p' || empty($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../connection.php';
$appointmentId = filter_input(
    $_SERVER['REQUEST_METHOD'] === 'POST' ? INPUT_POST : INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);
$patientLookup = $database->prepare('SELECT pid FROM patient WHERE pemail = ?');
$patientLookup->bind_param('s', $_SESSION['user']);
$patientLookup->execute();
$patient = $patientLookup->get_result()->fetch_assoc();

if (!$patient || !$appointmentId) {
    header('Location: appointment.php');
    exit;
}

$appointmentLookup = $database->prepare('SELECT appoid, scheduleid FROM appointment WHERE appoid = ? AND pid = ?');
$appointmentLookup->bind_param('ii', $appointmentId, $patient['pid']);
$appointmentLookup->execute();
$appointment = $appointmentLookup->get_result()->fetch_assoc();

if (!$appointment) {
    header('Location: appointment.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newScheduleId = filter_input(INPUT_POST, 'scheduleid', FILTER_VALIDATE_INT);

    if (!$newScheduleId) {
        $error = 'Choose an available appointment time.';
    } else {
        $database->begin_transaction();
        $lockAppointment = $database->prepare('SELECT appoid FROM appointment WHERE appoid = ? AND pid = ? FOR UPDATE');
        $lockAppointment->bind_param('ii', $appointmentId, $patient['pid']);
        $lockAppointment->execute();
        $appointmentExists = $lockAppointment->get_result()->num_rows === 1;

        $scheduleLookup = $database->prepare('SELECT nop FROM schedule WHERE scheduleid = ? AND scheduledate >= CURDATE() FOR UPDATE');
        $scheduleLookup->bind_param('i', $newScheduleId);
        $scheduleLookup->execute();
        $schedule = $scheduleLookup->get_result()->fetch_assoc();

        if (!$appointmentExists || !$schedule) {
            $database->rollback();
            $error = 'That appointment time is no longer available.';
        } else {
            $reservationsQuery = $database->prepare('SELECT apponum FROM appointment WHERE scheduleid = ? AND appoid <> ?');
            $reservationsQuery->bind_param('ii', $newScheduleId, $appointmentId);
            $reservationsQuery->execute();
            $occupiedNumbers = [];
            $reservations = $reservationsQuery->get_result();
            while ($reservation = $reservations->fetch_assoc()) {
                $occupiedNumbers[(int) $reservation['apponum']] = true;
            }

            $appointmentNumber = 1;
            while (isset($occupiedNumbers[$appointmentNumber]) && $appointmentNumber <= (int) $schedule['nop']) {
                $appointmentNumber++;
            }

            if ($appointmentNumber > (int) $schedule['nop']) {
                $database->rollback();
                $error = 'That appointment time has just filled up. Please choose another.';
            } else {
                $bookingDate = date('Y-m-d');
                $update = $database->prepare("UPDATE appointment SET scheduleid = ?, apponum = ?, appodate = ?, status = 'pending' WHERE appoid = ? AND pid = ?");
                $update->bind_param('iisii', $newScheduleId, $appointmentNumber, $bookingDate, $appointmentId, $patient['pid']);

                if (!$update->execute()) {
                    $database->rollback();
                    error_log('Unable to reschedule appointment: ' . $database->error);
                    http_response_code(500);
                    exit('Unable to update your appointment.');
                }

                $database->commit();
                header('Location: appointment.php?action=booking-updated');
                exit;
            }
        }
    }
}

$sessions = $database->prepare(
    'SELECT s.scheduleid, s.title, s.scheduledate, s.scheduletime, s.nop, d.docname,
        (SELECT COUNT(*) FROM appointment a WHERE a.scheduleid = s.scheduleid AND a.appoid <> ?) AS booked
     FROM schedule s
     INNER JOIN doctor d ON d.docid = s.docid
     WHERE s.scheduledate >= CURDATE()
     ORDER BY s.scheduledate, s.scheduletime'
);
$sessions->bind_param('i', $appointmentId);
$sessions->execute();
$availableSessions = $sessions->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin.css">
    <title>Uhai Bora | Reschedule</title>
</head>
<body>
    <main class="session-editor" style="max-width: 680px; margin: 70px auto;">
        <a href="appointment.php" class="care-link">← Back to my appointments</a>
        <h1>Choose a new appointment time</h1>
        <?php if ($error !== ''): ?>
            <p role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <form method="post" action="reschedule.php" class="reschedule-form">
            <input type="hidden" name="id" value="<?php echo (int) $appointmentId; ?>">
            <label for="scheduleid">Available sessions</label>
            <select id="scheduleid" name="scheduleid" class="input-text" required>
                <option value="">Choose a session</option>
                <?php while ($session = $availableSessions->fetch_assoc()): ?>
                    <?php if ((int) $session['booked'] < (int) $session['nop'] || (int) $session['scheduleid'] === (int) $appointment['scheduleid']): ?>
                        <option value="<?php echo (int) $session['scheduleid']; ?>">
                            <?php echo htmlspecialchars($session['title'] . ' — Dr. ' . $session['docname'] . ' — ' . $session['scheduledate'] . ' at ' . substr($session['scheduletime'], 0, 5), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endif; ?>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="login-btn btn-primary btn">Save new time</button>
        </form>
    </main>
</body>
</html>
