<?php
/**
 * Plugin Name: Doctor Appointment Booking
 * Description: A professional plugin for booking dental appointments.
 * Version: 1.0
 * Author: Gaurav Panchal 
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin activation
register_activation_hook(__FILE__, 'doctor_booking_activate');
add_action('admin_menu', 'doctor_booking_admin_menu');

// Enqueue scripts and styles
add_action('wp_enqueue_scripts', 'doctor_booking_scripts');
add_action('admin_enqueue_scripts', 'doctor_booking_admin_scripts');

function doctor_booking_activate()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Create tables
    $doctors_table = $wpdb->prefix . "booking_doctors";
    $services_table = $wpdb->prefix . "booking_services";
    $appointments_table = $wpdb->prefix . "booking_appointments";

    $sql_doctors = "CREATE TABLE $doctors_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        title varchar(255) DEFAULT '' NOT NULL,
        gdc_reg varchar(50) DEFAULT '' NOT NULL,
        speciality varchar(255) DEFAULT '' NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    $sql_services = "CREATE TABLE $services_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        duration_minutes int(11) NOT NULL,
        cost decimal(10, 2) NOT NULL,
        deposit decimal(10, 2) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    $sql_appointments = "CREATE TABLE $appointments_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        doctor_id bigint(20) NOT NULL,
        service_id bigint(20) NOT NULL,
        appointment_date date NOT NULL,
        appointment_time time NOT NULL,
        first_name varchar(255) NOT NULL,
        last_name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        phone varchar(50) NOT NULL,
        sex varchar(20) DEFAULT NULL,
        date_of_birth date DEFAULT NULL,
        notes text DEFAULT NULL,
        status varchar(50) DEFAULT 'pending' NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . "wp-admin/includes/upgrade.php");
    dbDelta($sql_doctors);
    dbDelta($sql_services);
    dbDelta($sql_appointments);

    // Insert default data
    doctor_booking_insert_default_data();
}

function doctor_booking_insert_default_data()
{
    global $wpdb;
    $doctors_table = $wpdb->prefix . 'booking_doctors';
    $services_table = $wpdb->prefix . 'booking_services';

    // Insert default doctors if not exists
    if (!$wpdb->get_var("SELECT COUNT(*) FROM $doctors_table")) {
        $wpdb->insert($doctors_table, [
            'name' => 'Dr Yazan Douedari',
            'title' => 'Dental Surgeon',
            'gdc_reg' => '290920',
            'speciality' => 'General Dentistry'
        ]);
        $wpdb->insert($doctors_table, [
            'name' => 'Dr Constantinos Christofides',
            'title' => 'Dental Surgeon',
            'gdc_reg' => '290920',
            'speciality' => 'Dental Surgery'
        ]);
    }

    // Insert default services
    if (!$wpdb->get_var("SELECT COUNT(*) FROM $services_table")) {
        $wpdb->insert($services_table, [
            'name' => 'Examination',
            'duration_minutes' => 20,
            'cost' => 35.00,
            'deposit' => 35.00
        ]);
        $wpdb->insert($services_table, [
            'name' => 'Smile Makeover Consultation',
            'duration_minutes' => 30,
            'cost' => 50.00,
            'deposit' => 25.00
        ]);
        $wpdb->insert($services_table, [
            'name' => 'Dental Cleaning',
            'duration_minutes' => 45,
            'cost' => 75.00,
            'deposit' => 35.00
        ]);
    }
}

function doctor_booking_scripts()
{
    wp_enqueue_script('jquery');
    wp_enqueue_script('doctor-booking-js', plugin_dir_url(__FILE__) . 'booking.js', ['jquery'], '1.0', true);
    wp_enqueue_style('doctor-booking-css', plugin_dir_url(__FILE__) . 'booking.css', [], '1.0');

    wp_localize_script('doctor-booking-js', 'doctor_booking_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('doctor_booking_nonce')
    ]);
}

function doctor_booking_admin_scripts($hook)
{
    if (strpos($hook, 'doctor-appointment') !== false) {
        wp_enqueue_style('doctor-booking-admin-css', plugin_dir_url(__FILE__) . 'admin.css', [], '1.0');
    }
}

// Admin menu
function doctor_booking_admin_menu()
{
    add_menu_page(
        'Doctor Appointments',
        'Doctor Appointments',
        'manage_options',
        'doctor-appointment-main',
        'doctor_appointment_main_page_html',
        'dashicons-calendar-alt',
        25
    );

    add_submenu_page(
        'doctor-appointment-main',
        'Appointments',
        'Appointments',
        'manage_options',
        'doctor-appointments-list',
        'doctor_appointments_list_page_html'
    );

    add_submenu_page(
        'doctor-appointment-main',
        'Services',
        'Services',
        'manage_options',
        'doctor-services',
        'doctor_services_page_html'
    );
}

// Admin pages
function doctor_appointment_main_page_html()
{
    if (isset($_POST['add_doctor_submit'])) {
        global $wpdb;
        $doctors_table = $wpdb->prefix . 'booking_doctors';

        $name = sanitize_text_field($_POST['doctor_name']);
        $title = sanitize_text_field($_POST['doctor_title']);
        $gdc_reg = sanitize_text_field($_POST['doctor_gdc']);
        $speciality = sanitize_text_field($_POST['doctor_speciality']);

        $wpdb->insert($doctors_table, [
            'name' => $name,
            'title' => $title,
            'gdc_reg' => $gdc_reg,
            'speciality' => $speciality,
        ]);
        echo "<div class='notice notice-success'><p>Doctor added successfully!</p></div>";
    }

    global $wpdb;
    $doctors_table = $wpdb->prefix . 'booking_doctors';
    $all_doctors = $wpdb->get_results("SELECT * FROM $doctors_table");
    ?>
    <div class="wrap">
        <h1><?php echo get_admin_page_title(); ?></h1>

        <h2>Add New Doctor</h2>
        <form method="POST" action="">
            <table class="form-table">
                <tr>
                    <th scope="row">Doctor's Name</th>
                    <td><input type="text" name="doctor_name" required /></td>
                </tr>
                <tr>
                    <th scope="row">Title</th>
                    <td><input type="text" name="doctor_title" placeholder="e.g., Dental Surgeon" /></td>
                </tr>
                <tr>
                    <th scope="row">GDC Reg Number</th>
                    <td><input type="text" name="doctor_gdc" /></td>
                </tr>
                <tr>
                    <th scope="row">Speciality</th>
                    <td><input type="text" name="doctor_speciality" /></td>
                </tr>
            </table>
            <?php submit_button('Add Doctor', 'primary', 'add_doctor_submit'); ?>
        </form>

        <hr>
        <h2>Existing Doctors</h2>
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Title</th>
                    <th>GDC Reg</th>
                    <th>Speciality</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($all_doctors): ?>
                    <?php foreach ($all_doctors as $doctor): ?>
                        <tr>
                            <td><?php echo esc_html($doctor->id); ?></td>
                            <td><?php echo esc_html($doctor->name); ?></td>
                            <td><?php echo esc_html($doctor->title); ?></td>
                            <td><?php echo esc_html($doctor->gdc_reg); ?></td>
                            <td><?php echo esc_html($doctor->speciality); ?></td>
                            <td>
                                <a href="#" class="button">Edit</a>
                                <a href="#" class="button">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No doctors found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function doctor_appointments_list_page_html()
{
    global $wpdb;
    $appointments_table = $wpdb->prefix . 'booking_appointments';
    $doctors_table = $wpdb->prefix . 'booking_doctors';
    $services_table = $wpdb->prefix . 'booking_services';

    $appointments = $wpdb->get_results("
        SELECT a.*, d.name as doctor_name, s.name as service_name, s.cost, s.deposit
        FROM $appointments_table a
        LEFT JOIN $doctors_table d ON a.doctor_id = d.id
        LEFT JOIN $services_table s ON a.service_id = s.id
        ORDER BY a.appointment_date DESC, a.appointment_time ASC
    ");
    ?>
    <div class="wrap">
        <h1>Appointments</h1>
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Patient</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Doctor</th>
                    <th>Service</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($appointments): ?>
                    <?php foreach ($appointments as $a): ?>
                        <tr>
                            <td><?php echo esc_html($a->id); ?></td>
                            <td><?php echo esc_html($a->first_name . ' ' . $a->last_name); ?></td>
                            <td><?php echo esc_html($a->email); ?></td>
                            <td><?php echo esc_html($a->phone); ?></td>
                            <td><?php echo esc_html($a->doctor_name); ?></td>
                            <td><?php echo esc_html($a->service_name); ?></td>
                            <td><?php echo esc_html(date('M j, Y', strtotime($a->appointment_date))); ?></td>
                            <td><?php echo esc_html(date('g:i A', strtotime($a->appointment_time))); ?></td>
                            <td><span
                                    class="status-<?php echo esc_attr($a->status); ?>"><?php echo esc_html(ucfirst($a->status)); ?></span>
                            </td>
                            <td>
                                <a href="#" class="button">View</a>
                                <a href="#" class="button">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10">No appointments found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function doctor_services_page_html()
{
    if (isset($_POST['add_service_submit'])) {
        global $wpdb;
        $services_table = $wpdb->prefix . 'booking_services';

        $wpdb->insert($services_table, [
            'name' => sanitize_text_field($_POST['service_name']),
            'duration_minutes' => intval($_POST['duration']),
            'cost' => floatval($_POST['cost']),
            'deposit' => floatval($_POST['deposit']),
        ]);
        echo "<div class='notice notice-success'><p>Service added successfully!</p></div>";
    }

    global $wpdb;
    $services_table = $wpdb->prefix . 'booking_services';
    $all_services = $wpdb->get_results("SELECT * FROM $services_table");
    ?>
    <div class="wrap">
        <h1>Services</h1>

        <h2>Add New Service</h2>
        <form method="POST" action="">
            <table class="form-table">
                <tr>
                    <th scope="row">Service Name</th>
                    <td><input type="text" name="service_name" required /></td>
                </tr>
                <tr>
                    <th scope="row">Duration (minutes)</th>
                    <td><input type="number" name="duration" required /></td>
                </tr>
                <tr>
                    <th scope="row">Cost (£)</th>
                    <td><input type="number" step="0.01" name="cost" required /></td>
                </tr>
                <tr>
                    <th scope="row">Deposit (£)</th>
                    <td><input type="number" step="0.01" name="deposit" required /></td>
                </tr>
            </table>
            <?php submit_button('Add Service', 'primary', 'add_service_submit'); ?>
        </form>

        <hr>
        <h2>Existing Services</h2>
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Duration</th>
                    <th>Cost</th>
                    <th>Deposit</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($all_services): ?>
                    <?php foreach ($all_services as $service): ?>
                        <tr>
                            <td><?php echo esc_html($service->id); ?></td>
                            <td><?php echo esc_html($service->name); ?></td>
                            <td><?php echo esc_html($service->duration_minutes); ?> min</td>
                            <td>£<?php echo esc_html(number_format($service->cost, 2)); ?></td>
                            <td>£<?php echo esc_html(number_format($service->deposit, 2)); ?></td>
                            <td>
                                <a href="#" class="button">Edit</a>
                                <a href="#" class="button">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No services found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Shortcode for booking form
add_shortcode('appointment_booking_form', 'appointment_booking_form_shortcode');

function appointment_booking_form_shortcode()
{
    global $wpdb;
    $doctors_table = $wpdb->prefix . 'booking_doctors';
    $services_table = $wpdb->prefix . 'booking_services';

    $doctors = $wpdb->get_results("SELECT * FROM $doctors_table");
    $services = $wpdb->get_results("SELECT * FROM $services_table");

    ob_start();
    ?>
    <div class="dental-booking-container">
        <div class="booking-header">
            <div class="booking-logo">
                <h2>Book with The Dental Care Centre</h2>
            </div>
            <div class="booking-steps">
                <div class="step active" id="step-1-indicator">
                    <span class="step-number">1</span>
                    <span class="step-text">Appointment Details</span>
                </div>
                <div class="step" id="step-2-indicator">
                    <span class="step-number">2</span>
                    <span class="step-text">Personal Details</span>
                </div>
            </div>
        </div>

        <!-- Step 1: Appointment Details -->
        <div id="booking-step-1" class="booking-step active">
            <form id="appointment-details-form">
                <div class="question-group">
                    <h3>Have you booked an appointment with us before?</h3>
                    <div class="button-group">
                        <button type="button" class="option-button" data-value="existing">
                            <strong>Yes</strong><br>
                            <small>I'm an existing patient</small>
                        </button>
                        <button type="button" class="option-button" data-value="new">
                            <strong>No</strong><br>
                            <small>I'm a new patient</small>
                        </button>
                    </div>
                </div>

                <div class="question-group">
                    <h3>What can we help you with?</h3>
                    <select name="service_id" id="service-select" required>
                        <option value="">Choose a service...</option>
                        <?php foreach ($services as $service): ?>
                            <option value="<?php echo esc_attr($service->id); ?>"
                                data-cost="<?php echo esc_attr($service->cost); ?>"
                                data-deposit="<?php echo esc_attr($service->deposit); ?>"
                                data-duration="<?php echo esc_attr($service->duration_minutes); ?>">
                                <?php echo esc_html($service->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="question-group">
                    <h3>Who would you like to see?</h3>
                    <select name="doctor_id" id="doctor-select" required>
                        <option value="">Choose a doctor...</option>
                        <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo esc_attr($doctor->id); ?>">
                                <?php echo esc_html($doctor->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="question-group">
                    <h3>When would you like your appointment?</h3>
                    <div class="date-time-selector">
                        <div class="date-navigation">
                            <button type="button" id="prev-week" class="nav-button">&lt;</button>
                            <div class="date-range" id="date-range-display"></div>
                            <button type="button" id="next-week" class="nav-button">&gt;</button>
                        </div>
                        <div class="date-grid" id="date-grid"></div>
                        <div class="time-slots" id="time-slots"></div>
                    </div>
                </div>

                <button type="button" id="next-to-personal" class="btn btn-primary" disabled>
                    NEXT: PERSONAL DETAILS
                </button>
            </form>
        </div>

        <!-- Step 2: Personal Details -->
        <div id="booking-step-2" class="booking-step">
            <div class="appointment-summary" id="appointment-summary"></div>

            <form id="personal-details-form">
                <h3>A little bit about you</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label>First Name <span class="required">*</span></label>
                        <input type="text" name="first_name" required placeholder="Enter your first name">
                    </div>
                    <div class="form-group">
                        <label>Last Name <span class="required">*</span></label>
                        <input type="text" name="last_name" required placeholder="Enter your last name">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email Address <span class="required">*</span></label>
                        <input type="email" name="email" required placeholder="Enter your email address">
                    </div>
                    <div class="form-group">
                        <label>Sex <span class="required">*</span></label>
                        <select name="sex" required>
                            <option value="">Choose your sex</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Date of Birth <span class="required">*</span></label>
                        <input type="text" name="date_of_birth" placeholder="DD/MM/YYYY">
                    </div>
                    <div class="form-group">
                        <label>Phone Number <span class="required">*</span></label>
                        <div class="phone-input">
                            <select class="country-code">
                                <option value="+44">🇬🇧 +44</option>
                            </select>
                            <input type="tel" name="phone" required placeholder="Phone number">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Medical considerations or additional information</label>
                    <textarea name="notes" rows="4" placeholder="Please type here..."></textarea>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="terms" required>
                        I acknowledge and accept the <a href="#" target="_blank">Terms of Use</a> <span
                            class="required">*</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary" id="book-appointment">
                    BOOK APPOINTMENT
                </button>
            </form>
        </div>
    </div>

    <div id="booking-success" class="booking-success" style="display: none;">
        <div class="success-message">
            <h2>✅ Appointment Booked Successfully!</h2>
            <p>We've sent you a confirmation email with all the details.</p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// AJAX handlers
add_action('wp_ajax_get_available_slots', 'doctor_booking_get_available_slots');
add_action('wp_ajax_nopriv_get_available_slots', 'doctor_booking_get_available_slots');

function doctor_booking_get_available_slots()
{
    check_ajax_referer('doctor_booking_nonce', 'nonce');

    global $wpdb;
    $appointments_table = $wpdb->prefix . 'booking_appointments';

    $doctor_id = intval($_POST['doctor_id']);
    $date = sanitize_text_field($_POST['date']);

    // Get booked slots for this doctor and date
    $booked_slots = $wpdb->get_col($wpdb->prepare(
        "SELECT appointment_time FROM $appointments_table 
         WHERE doctor_id = %d AND appointment_date = %s AND status != 'cancelled'",
        $doctor_id,
        $date
    ));

    // Generate all possible time slots (9 AM to 5 PM, 30-minute intervals)
    $all_slots = [];
    for ($h = 9; $h < 17; $h++) {
        for ($m = 0; $m < 60; $m += 30) {
            $time = sprintf("%02d:%02d:00", $h, $m);
            $all_slots[] = $time;
        }
    }

    // Remove booked slots
    $available_slots = array_diff($all_slots, $booked_slots);

    wp_send_json_success(array_values($available_slots));
}

add_action('wp_ajax_book_appointment', 'doctor_booking_save_appointment');
add_action('wp_ajax_nopriv_book_appointment', 'doctor_booking_save_appointment');

function doctor_booking_save_appointment()
{
    check_ajax_referer('doctor_booking_nonce', 'nonce');

    global $wpdb;
    $appointments_table = $wpdb->prefix . 'booking_appointments';

    $result = $wpdb->insert($appointments_table, [
        'doctor_id' => intval($_POST['doctor_id']),
        'service_id' => intval($_POST['service_id']),
        'appointment_date' => sanitize_text_field($_POST['appointment_date']),
        'appointment_time' => sanitize_text_field($_POST['appointment_time']),
        'first_name' => sanitize_text_field($_POST['first_name']),
        'last_name' => sanitize_text_field($_POST['last_name']),
        'email' => sanitize_email($_POST['email']),
        'phone' => sanitize_text_field($_POST['phone']),
        'sex' => sanitize_text_field($_POST['sex']),
        'date_of_birth' => sanitize_text_field($_POST['date_of_birth']),
        'notes' => sanitize_textarea_field($_POST['notes']),
        'status' => 'confirmed'
    ]);

    if ($result) {
        // Send confirmation email here if needed
        wp_send_json_success('Appointment booked successfully!');
    } else {
        wp_send_json_error('Failed to book appointment. Please try again.');
    }
}
?>