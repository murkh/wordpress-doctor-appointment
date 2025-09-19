jQuery(document).ready(function ($) {
  let currentWeekOffset = 0;
  let selectedDate = null;
  let selectedTime = null;
  let selectedDoctor = null;
  let selectedService = null;
  let patientType = null;

  // Initialize the booking system
  function initBooking() {
    generateDateGrid();
    setupEventListeners();
  }

  // Setup all event listeners
  function setupEventListeners() {
    // Patient type selection
    $(".option-button").on("click", function () {
      const group = $(this).parent();
      group.find(".option-button").removeClass("selected");
      $(this).addClass("selected");
      patientType = $(this).data("value");
    });

    // Service selection
    $("#service-select").on("change", function () {
      selectedService = $(this).val();
      if (selectedService) {
        updateServiceInfo();
      }
    });

    // Doctor selection
    $("#doctor-select").on("change", function () {
      selectedDoctor = $(this).val();
      if (selectedDate && selectedDoctor) {
        loadTimeSlots(selectedDate, selectedDoctor);
      }
      checkStep1Complete();
    });

    // Date navigation
    $("#prev-week").on("click", function () {
      currentWeekOffset--;
      generateDateGrid();
    });

    $("#next-week").on("click", function () {
      currentWeekOffset++;
      generateDateGrid();
    });

    // Date selection
    $(document).on("click", ".date-option", function () {
      $(".date-option").removeClass("selected");
      $(this).addClass("selected");
      selectedDate = $(this).data("date");

      if (selectedDoctor) {
        loadTimeSlots(selectedDate, selectedDoctor);
      }
      checkStep1Complete();
    });

    // Time slot selection
    $(document).on("click", ".time-slot:not(.booked)", function () {
      $(".time-slot").removeClass("selected");
      $(this).addClass("selected");
      selectedTime = $(this).data("time");
      checkStep1Complete();
    });

    // Next to personal details
    $("#next-to-personal").on("click", function () {
      if (validateStep1()) {
        showStep2();
      }
    });

    // Form submission
    $("#personal-details-form").on("submit", function (e) {
      e.preventDefault();
      if (validateStep2()) {
        bookAppointment();
      }
    });

    // Change appointment details link
    $(document).on("click", ".change-link", function (e) {
      e.preventDefault();
      showStep1();
    });
  }

  // Generate date grid for the week
  function generateDateGrid() {
    const today = new Date();
    const startDate = new Date(today);
    startDate.setDate(today.getDate() + currentWeekOffset * 7);

    // Don't allow past dates
    if (currentWeekOffset < 0) {
      currentWeekOffset = 0;
      startDate.setTime(today.getTime());
    }

    const endDate = new Date(startDate);
    endDate.setDate(startDate.getDate() + 6);

    // Update date range display
    const monthNames = [
      "Jan",
      "Feb",
      "Mar",
      "Apr",
      "May",
      "Jun",
      "Jul",
      "Aug",
      "Sep",
      "Oct",
      "Nov",
      "Dec",
    ];

    let rangeText;
    if (startDate.getMonth() === endDate.getMonth()) {
      rangeText = `${startDate.getDate()} - ${endDate.getDate()} ${
        monthNames[startDate.getMonth()]
      } ${startDate.getFullYear()}`;
    } else {
      rangeText = `${startDate.getDate()} ${
        monthNames[startDate.getMonth()]
      } - ${endDate.getDate()} ${
        monthNames[endDate.getMonth()]
      } ${startDate.getFullYear()}`;
    }

    $("#date-range-display").text(rangeText);

    // Generate date options (5 days - weekdays only)
    const dateGrid = $("#date-grid");
    dateGrid.empty();

    for (let i = 0; i < 5; i++) {
      const date = new Date(startDate);
      date.setDate(startDate.getDate() + i);

      // Skip weekends
      if (date.getDay() === 0 || date.getDay() === 6) {
        continue;
      }

      const dateStr = formatDate(date);
      const dayNames = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

      const dateOption = $(`
                <div class="date-option" data-date="${dateStr}">
                    <div class="date-day">${dayNames[date.getDay()]}, ${date
        .getDate()
        .toString()
        .padStart(2, "0")} ${monthNames[date.getMonth()]}</div>
                </div>
            `);

      dateGrid.append(dateOption);
    }

    // Clear time slots when dates change
    $("#time-slots").empty();
    selectedDate = null;
    selectedTime = null;
    checkStep1Complete();
  }

  // Load available time slots for selected date and doctor
  function loadTimeSlots(date, doctorId) {
    const timeSlots = $("#time-slots");
    timeSlots.html('<div class="loading">Loading available times...</div>');

    $.ajax({
      url: doctor_booking_ajax.ajax_url,
      type: "POST",
      data: {
        action: "get_available_slots",
        doctor_id: doctorId,
        date: date,
        nonce: doctor_booking_ajax.nonce,
      },
      success: function (response) {
        if (response.success) {
          displayTimeSlots(response.data);
        } else {
          timeSlots.html(
            '<div class="error-message">Failed to load time slots. Please try again.</div>'
          );
        }
      },
      error: function () {
        timeSlots.html(
          '<div class="error-message">Failed to load time slots. Please try again.</div>'
        );
      },
    });
  }

  // Display time slots
  function displayTimeSlots(availableSlots) {
    const timeSlots = $("#time-slots");
    timeSlots.empty();

    // All possible time slots
    const allSlots = [
      "09:00:00",
      "09:30:00",
      "10:00:00",
      "10:30:00",
      "11:00:00",
      "11:30:00",
      "12:00:00",
      "12:30:00",
      "13:00:00",
      "13:30:00",
      "14:00:00",
      "14:30:00",
      "15:00:00",
      "15:30:00",
      "16:00:00",
      "16:30:00",
    ];

    allSlots.forEach(function (slot) {
      const time = slot.substring(0, 5); // Remove seconds
      const displayTime = formatTime(time);
      const isAvailable = availableSlots.includes(slot);

      const timeSlot = $(`
                <div class="time-slot ${
                  isAvailable ? "" : "booked"
                }" data-time="${slot}">
                    ${displayTime}${isAvailable ? "" : " (Booked)"}
                </div>
            `);

      timeSlots.append(timeSlot);
    });
  }

  // Check if step 1 is complete
  function checkStep1Complete() {
    const isComplete =
      patientType &&
      selectedService &&
      selectedDoctor &&
      selectedDate &&
      selectedTime;
    $("#next-to-personal").prop("disabled", !isComplete);
  }

  // Validate step 1
  function validateStep1() {
    if (!patientType) {
      alert("Please select if you are an existing or new patient.");
      return false;
    }
    if (!selectedService) {
      alert("Please select a service.");
      return false;
    }
    if (!selectedDoctor) {
      alert("Please select a doctor.");
      return false;
    }
    if (!selectedDate) {
      alert("Please select a date.");
      return false;
    }
    if (!selectedTime) {
      alert("Please select a time.");
      return false;
    }
    return true;
  }

  // Show step 2
  function showStep2() {
    $("#booking-step-1").removeClass("active");
    $("#booking-step-2").addClass("active");
    $("#step-1-indicator").removeClass("active");
    $("#step-2-indicator").addClass("active");

    generateAppointmentSummary();
    $("html, body").animate({ scrollTop: 0 }, 300);
  }

  // Show step 1
  function showStep1() {
    $("#booking-step-2").removeClass("active");
    $("#booking-step-1").addClass("active");
    $("#step-2-indicator").removeClass("active");
    $("#step-1-indicator").addClass("active");
    $("html, body").animate({ scrollTop: 0 }, 300);
  }

  // Generate appointment summary
  function generateAppointmentSummary() {
    const serviceName = $("#service-select option:selected").text();
    const doctorName = $("#doctor-select option:selected").text();
    const serviceOption = $("#service-select option:selected");
    const cost = serviceOption.data("cost");
    const deposit = serviceOption.data("deposit");
    const duration = serviceOption.data("duration");

    const formattedDate = formatDisplayDate(selectedDate);
    const formattedTime = formatTime(selectedTime.substring(0, 5));

    const summary = `
            <div class="summary-row">
                <span class="summary-label">Service</span>
                <span class="summary-value">${serviceName}</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Provider</span>
                <span class="summary-value">${doctorName}</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Date and Time</span>
                <span class="summary-value">${formattedDate} at ${formattedTime}</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Duration</span>
                <span class="summary-value">${duration} minutes</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Cost</span>
                <span class="summary-value">£${parseFloat(cost).toFixed(
                  2
                )}</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Deposit</span>
                <span class="summary-value">£${parseFloat(deposit).toFixed(
                  2
                )}</span>
            </div>
            <div style="margin-top: 15px;">
                <a href="#" class="change-link">Change Appointment Details</a>
            </div>
        `;

    $("#appointment-summary").html(summary);
  }

  // Validate step 2
  function validateStep2() {
    let isValid = true;
    const requiredFields = [
      "first_name",
      "last_name",
      "email",
      "sex",
      "date_of_birth",
      "phone",
    ];

    requiredFields.forEach(function (field) {
      const input = $(`input[name="${field}"], select[name="${field}"]`);
      if (!input.val().trim()) {
        input.css("border-color", "#e74c3c");
        isValid = false;
      } else {
        input.css("border-color", "#ddd");
      }
    });

    if (!$('input[name="terms"]').is(":checked")) {
      alert("Please accept the Terms of Use.");
      isValid = false;
    }

    if (!isValid) {
      alert("Please fill in all required fields.");
    }

    return isValid;
  }

  // Book the appointment
  function bookAppointment() {
    const submitBtn = $("#book-appointment");
    submitBtn.prop("disabled", true).text("BOOKING...");

    const formData = {
      action: "book_appointment",
      nonce: doctor_booking_ajax.nonce,
      doctor_id: selectedDoctor,
      service_id: selectedService,
      appointment_date: selectedDate,
      appointment_time: selectedTime,
      first_name: $('input[name="first_name"]').val(),
      last_name: $('input[name="last_name"]').val(),
      email: $('input[name="email"]').val(),
      phone: $('input[name="phone"]').val(),
      sex: $('select[name="sex"]').val(),
      date_of_birth: $('input[name="date_of_birth"]').val(),
      notes: $('textarea[name="notes"]').val(),
    };

    $.ajax({
      url: doctor_booking_ajax.ajax_url,
      type: "POST",
      data: formData,
      success: function (response) {
        if (response.success) {
          showSuccessMessage();
        } else {
          alert("Booking failed: " + (response.data || "Unknown error"));
          submitBtn.prop("disabled", false).text("BOOK APPOINTMENT");
        }
      },
      error: function () {
        alert("Booking failed. Please try again.");
        submitBtn.prop("disabled", false).text("BOOK APPOINTMENT");
      },
    });
  }

  // Show success message
  function showSuccessMessage() {
    $(".dental-booking-container").hide();
    $("#booking-success").show();
    $("html, body").animate({ scrollTop: 0 }, 300);
  }

  // Utility functions
  function formatDate(date) {
    return date.toISOString().split("T")[0];
  }

  function formatDisplayDate(dateStr) {
    const date = new Date(dateStr);
    const options = {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
    };
    return date.toLocaleDateString("en-GB", options);
  }

  function formatTime(timeStr) {
    const [hours, minutes] = timeStr.split(":");
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? "pm" : "am";
    const displayHour = hour % 12 || 12;
    return `${displayHour.toString().padStart(2, "0")}:${minutes}${ampm}`;
  }

  function updateServiceInfo() {
    const serviceOption = $("#service-select option:selected");
    const cost = serviceOption.data("cost");
    const deposit = serviceOption.data("deposit");
    const duration = serviceOption.data("duration");

    // You can display this information somewhere if needed
    console.log(
      `Service: ${serviceOption.text()}, Cost: £${cost}, Deposit: £${deposit}, Duration: ${duration} min`
    );
  }

  // Initialize everything
  initBooking();
});
