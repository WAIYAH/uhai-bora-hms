<?php
/*
 * Accessible form fields and server-side validators. Each field renders its
 * label, hint and error message and links them with aria-describedby.
 */

function field_id(string $name): string
{
    return 'field-' . preg_replace('/[^a-z0-9]+/i', '-', $name);
}

function html_attrs(array $attrs): string
{
    $html = '';
    foreach ($attrs as $key => $value) {
        if ($value === null || $value === false) {
            continue;
        }
        $html .= $value === true ? ' ' . e($key) : ' ' . e($key) . '="' . e($value) . '"';
    }
    return $html;
}

/** Shared wrapper: label, hint, error, then the control produced by $control($describedBy, $invalid). */
function field_wrap(array $o, callable $control): string
{
    $name = $o['name'];
    $id = $o['id'] ?? field_id($name);
    $error = $o['error'] ?? field_error($name);
    $describedBy = [];
    $hintHtml = '';
    if (!empty($o['hint'])) {
        $describedBy[] = $id . '-hint';
        $hintHtml = '<p class="field__hint" id="' . e($id) . '-hint">' . e($o['hint']) . '</p>';
    }
    $errorHtml = '';
    if ($error !== '') {
        $describedBy[] = $id . '-error';
        $errorHtml = '<p class="field__error" id="' . e($id) . '-error"><span class="visually-hidden">Error: </span>' . e($error) . '</p>';
    }
    $optional = empty($o['required']) && empty($o['hide_optional']) ? ' <span class="field__optional">(optional)</span>' : '';
    return '<div class="field' . ($error !== '' ? ' field--error' : '') . (!empty($o['class']) ? ' ' . e($o['class']) : '') . '"'
        . (!empty($o['wrapper_attrs']) ? html_attrs($o['wrapper_attrs']) : '') . '>'
        . '<label class="field__label" for="' . e($id) . '">' . e($o['label']) . $optional . '</label>'
        . $hintHtml . $errorHtml
        . $control($id, implode(' ', $describedBy), $error !== '')
        . '</div>';
}

function input_field(array $o): string
{
    $o += ['type' => 'text', 'required' => true, 'attrs' => []];
    $value = array_key_exists('value', $o) ? (string) $o['value'] : old($o['name'], $o['default'] ?? '');
    if ($o['type'] === 'password') {
        $value = '';
    }
    return field_wrap($o, function ($id, $describedBy, $invalid) use ($o, $value) {
        $attrs = array_merge([
            'class' => 'input',
            'id' => $id,
            'name' => $o['name'],
            'type' => $o['type'],
            'value' => $o['type'] === 'password' ? null : $value,
            'required' => !empty($o['required']),
            'aria-describedby' => $describedBy !== '' ? $describedBy : null,
            'aria-invalid' => $invalid ? 'true' : null,
            'autocomplete' => $o['autocomplete'] ?? null,
        ], $o['attrs']);
        $input = '<input' . html_attrs($attrs) . '>';
        if ($o['type'] === 'password') {
            return '<div class="password-input">' . $input
                . '<button type="button" class="password-input__toggle" data-password-toggle="' . e($id) . '" aria-pressed="false">'
                . '<span data-label>Show</span><span class="visually-hidden"> password</span></button></div>';
        }
        return $input;
    });
}

function select_field(array $o): string
{
    $o += ['required' => true, 'attrs' => [], 'options' => [], 'placeholder' => null];
    $selected = array_key_exists('value', $o) ? (string) $o['value'] : old($o['name'], $o['default'] ?? '');
    return field_wrap($o, function ($id, $describedBy, $invalid) use ($o, $selected) {
        $attrs = array_merge([
            'class' => 'input select',
            'id' => $id,
            'name' => $o['name'],
            'required' => !empty($o['required']),
            'aria-describedby' => $describedBy !== '' ? $describedBy : null,
            'aria-invalid' => $invalid ? 'true' : null,
        ], $o['attrs']);
        $html = '<select' . html_attrs($attrs) . '>';
        if ($o['placeholder'] !== null) {
            $html .= '<option value="">' . e($o['placeholder']) . '</option>';
        }
        foreach ($o['options'] as $value => $label) {
            $html .= '<option value="' . e($value) . '"' . ((string) $value === $selected ? ' selected' : '') . '>' . e($label) . '</option>';
        }
        return $html . '</select>';
    });
}

function textarea_field(array $o): string
{
    $o += ['required' => true, 'attrs' => [], 'rows' => 3];
    $value = array_key_exists('value', $o) ? (string) $o['value'] : old($o['name'], $o['default'] ?? '');
    return field_wrap($o, function ($id, $describedBy, $invalid) use ($o, $value) {
        $attrs = array_merge([
            'class' => 'input textarea',
            'id' => $id,
            'name' => $o['name'],
            'rows' => (string) $o['rows'],
            'required' => !empty($o['required']),
            'aria-describedby' => $describedBy !== '' ? $describedBy : null,
            'aria-invalid' => $invalid ? 'true' : null,
            'autocomplete' => $o['autocomplete'] ?? null,
        ], $o['attrs']);
        return '<textarea' . html_attrs($attrs) . '>' . e($value) . '</textarea>';
    });
}

function checkbox_field(array $o): string
{
    $o += ['required' => true, 'value' => '1', 'attrs' => []];
    $id = $o['id'] ?? field_id($o['name']);
    $error = field_error($o['name']);
    $checked = array_key_exists('checked', $o) ? (bool) $o['checked'] : old($o['name']) === $o['value'];
    $attrs = array_merge([
        'class' => 'checkbox__input',
        'id' => $id,
        'type' => 'checkbox',
        'name' => $o['name'],
        'value' => $o['value'],
        'checked' => $checked,
        'required' => !empty($o['required']),
        'aria-describedby' => $error !== '' ? $id . '-error' : null,
        'aria-invalid' => $error !== '' ? 'true' : null,
    ], $o['attrs']);
    return '<div class="checkbox' . ($error !== '' ? ' field--error' : '') . '">'
        . ($error !== '' ? '<p class="field__error" id="' . e($id) . '-error"><span class="visually-hidden">Error: </span>' . e($error) . '</p>' : '')
        . '<input' . html_attrs($attrs) . '>'
        . '<label class="checkbox__label" for="' . e($id) . '">' . $o['label_html'] . '</label></div>';
}

/** Summary of all errors at the top of a form, linking to each field. */
function error_summary(?array $errors = null, array $ids = []): string
{
    $errors = $errors ?? form_errors();
    if (!$errors) {
        return '';
    }
    $items = '';
    foreach ($errors as $name => $message) {
        $target = $ids[$name] ?? field_id((string) $name);
        $items .= '<li><a href="#' . e($target) . '">' . e($message) . '</a></li>';
    }
    return '<div class="error-summary" tabindex="-1" data-error-summary aria-labelledby="error-summary-title">'
        . '<h2 id="error-summary-title" class="error-summary__title">' . icon('alert') . 'There is a problem</h2>'
        . '<ul class="error-summary__list">' . $items . '</ul></div>';
}

/* ---------- Validators: each returns an error message, or '' when valid ---------- */

function validate_person_name(string $value, string $label): string
{
    if ($value === '') {
        return "Enter your {$label}.";
    }
    if (mb_strlen($value) > 120) {
        return ucfirst($label) . ' must be 120 characters or fewer.';
    }
    if (preg_match("/^[\\p{L}\\p{M}][\\p{L}\\p{M}'’ .\\-]*$/u", $value) !== 1) {
        return ucfirst($label) . " can only include letters, spaces, hyphens, full stops and apostrophes.";
    }
    return '';
}

function validate_full_name(string $value, string $label = 'full name'): string
{
    $error = validate_person_name($value, $label);
    if ($error === '' && mb_strlen($value) < 2) {
        return 'Enter the ' . $label . '.';
    }
    return $error;
}

function validate_email_address(string $value): string
{
    if ($value === '') {
        return 'Enter an email address.';
    }
    if (mb_strlen($value) > 255 || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
        return 'Enter an email address in the correct format, like name@example.com.';
    }
    return '';
}

/** Kenyan numbers (07…, 01…, +254…) and international numbers with a country code. */
function validate_phone(string $value): string
{
    if ($value === '') {
        return 'Enter a phone number.';
    }
    $digits = preg_replace('/\D+/', '', $value);
    if (preg_match('/^\+?[0-9][0-9 ()\-]{6,24}$/', $value) !== 1 || strlen($digits) < 9 || strlen($digits) > 15) {
        return 'Enter a phone number, like 0712 345 678 or +254 712 345 678.';
    }
    return '';
}

function validate_id_number(string $value): string
{
    if ($value === '') {
        return 'Enter your ID or passport number.';
    }
    if (preg_match('/^[A-Za-z0-9][A-Za-z0-9 \/\-]{2,31}$/', $value) !== 1) {
        return 'Enter an ID or passport number using letters, numbers, spaces, hyphens or slashes (3 to 32 characters).';
    }
    return '';
}

function validate_date_of_birth(string $value): string
{
    if ($value === '') {
        return 'Enter a date of birth.';
    }
    if (!is_valid_date($value)) {
        return 'Enter a real date of birth.';
    }
    if ($value > today()) {
        return 'Date of birth must be today or in the past.';
    }
    if (age_on($value) > 120) {
        return 'Check the year of birth.';
    }
    return '';
}

function validate_text(string $value, string $label, int $min, int $max, bool $required = true): string
{
    if ($value === '') {
        return $required ? "Enter {$label}." : '';
    }
    $length = mb_strlen($value);
    if ($length < $min) {
        return ucfirst($label) . " must be at least {$min} characters.";
    }
    if ($length > $max) {
        return ucfirst($label) . " must be {$max} characters or fewer.";
    }
    return '';
}

/** True when an email is already used by another account. */
function email_taken(string $email, string $exceptEmail = ''): bool
{
    return db_value('SELECT 1 FROM webuser WHERE email = ? AND email <> ?', [$email, $exceptEmail]) !== null;
}
