// =====================================================
// JAVASCRIPT FUNCTIONS FOR BUS RESERVATION SYSTEM
// =====================================================

// Auto-dismiss alerts after 5 seconds
document.addEventListener("DOMContentLoaded", function () {
  const alerts = document.querySelectorAll(".alert:not(.alert-permanent)");
  alerts.forEach(function (alert) {
    setTimeout(function () {
      const bsAlert = new bootstrap.Alert(alert);
      bsAlert.close();
    }, 5000);
  });
});

// Date picker restriction (no past dates)
function setMinDate() {
  const dateInputs = document.querySelectorAll('input[type="date"]');
  const today = new Date().toISOString().split("T")[0];
  dateInputs.forEach((input) => {
    input.setAttribute("min", today);
  });
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", function () {
  setMinDate();

  // Initialize tooltips
  const tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });
});
