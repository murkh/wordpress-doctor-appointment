<?php
/**
 * Plugin Name: Doctor Appointment
 * Description: A plugin for booking dental appointments.
 * Version: 1.0
 * Author: Gaurav Panchal 
 */

function doctor_booking_activate()
{

    // We need to access the Wordpress Database Functions
    global $wpdb;

    // This is the set for the database tables
    $charset_collate = $wpdb->get_charset_collate();

    $doctors_table_name = $wpdb->prefix . "booking_doctors";
    $services_table_name = $wpdb->prefix . "booking_services";


    // SQL STATEMENT TO CREATE THE DOCTOR TABLE
    $sql_doctors = "CREATE TABLE $doctors_table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        title varchar(255) DEFAULT '' NOT NULL,
        gdc_reg varchar(50) DEFAULT '' NOT NULL,
        speciality varchar(255) DEFAULT '' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";


    // SQL STATEMENT TO CREATE THE SERVICES TABLE
    $sql_services = "CREATE TABLE $services_table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        duration_minutes int(11) NOT NULL,
        cost decimal(10, 2) NOT NULL,
        deposit decimal(10, 2) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // We need this file to run the SQL.
    require_once(ABSPATH . "wp-admin/includes/upgrade.php");

    // Execute the SQL Queries
    dbDelta($sql_doctors);
    dbDelta($sql_services);
}


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
}

add_action('admin_menu', 'doctor_booking_admin_menu');
register_activation_hook(file: __FILE__, callback: "doctor_booking_activate");

function doctor_appointment_main_page_html()
{
    if (isset($_POST['add_doctor_submit'])) {
        global $wpdb;
        $doctors_table_name = $wpdb->prefix . 'booking_doctors';

        $name = sanitize_text_field($_POST['doctor_name']);
        $title = sanitize_text_field($_POST['doctor_title']);
        $gdc_reg = sanitize_text_field($_POST['doctor_gdc']);
        $speciality = sanitize_text_field($_POST['doctor_speciality']);

        $wpdb->insert(
            $doctors_table_name,
            array(
                'name' => $name,
                'title' => $title,
                'gdc_reg' => $gdc_reg,
                'speciality' => $speciality,
            )
        );
        echo "<div class='notice notice-success'><p>Doctor added successfully!</p></div>";
    }

    ?>
    <div class="wrap">
        <h1><?php echo get_admin_page_title(); ?></h1>
        <p>Welcome to the main booking page. Here we will manage appointments.</p>

        <h2>Add a New Doctor</h2>
        <form method="POST" action="">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Doctor's Name</th>
                    <td><input type="text" name="doctor_name" value="" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Title (e.g., Dental Surgeon)</th>
                    <td><input type="text" name="doctor_title" value="" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">GDC Reg Number</th>
                    <td><input type="text" name="doctor_gdc" value="" /></td>
                </tr>
            </table>
            <?php submit_button('Add Doctor', 'primary', 'add_doctor_submit'); ?>
        </form>
    </div>
    <?php
}


