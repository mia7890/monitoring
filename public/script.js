/* =========================================================
   MONITORING SYSTEM
   Shared behaviors for authenticated pages.
   (Sidebar toggle is handled inline in the app layout.)
   ========================================================= */

// =========================================================
// CURRENT DATE (fallback for #currentDate)
// =========================================================

const currentDate = document.getElementById("currentDate");

if (currentDate) {
    currentDate.textContent = new Date().toLocaleDateString(
        "en-US",
        {
            year: "numeric",
            month: "long",
            day: "numeric"
        }
    );
}