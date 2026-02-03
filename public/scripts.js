document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("pantryForm");

  if (!form) return;

  form.addEventListener("submit", (e) => {
    const qty = form.querySelector("input[name='quantity']").value;
    if (qty <= 0) {
      alert("Quantity must be greater than 0");
      e.preventDefault();
    }
  });
});
