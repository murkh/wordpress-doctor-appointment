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

register_activation_hook(file: __FILE__, callback: "doctor_booking_activate");
