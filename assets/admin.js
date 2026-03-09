(function () {
  function normalizeFieldKey(value) {
    return String(value || "")
      .trim()
      .toLowerCase()
      .replace(/[^a-z0-9_]/g, "_")
      .replace(/_+/g, "_")
      .replace(/^_+|_+$/g, "");
  }

  function updateRowPlaceholder(row) {
    if (!(row instanceof HTMLElement)) {
      return;
    }

    var keyInput = row.querySelector(".mlf-field-key");
    var placeholderInput = row.querySelector(".mlf-placeholder-value");
    if (!(keyInput instanceof HTMLInputElement) || !(placeholderInput instanceof HTMLInputElement)) {
      return;
    }

    var normalized = normalizeFieldKey(keyInput.value);
    placeholderInput.value = normalized ? "{{" + normalized + "}}" : "";
  }

  function updateAllPlaceholders(tbody) {
    tbody.querySelectorAll(".mlf-field-row").forEach(updateRowPlaceholder);
  }

  function init() {
    var addButton = document.getElementById("mlf-add-row");
    var tbody = document.getElementById("mlf-fields-body");
    var template = document.getElementById("mlf-row-template");

    if (!addButton || !tbody || !template) {
      return;
    }

    updateAllPlaceholders(tbody);

    addButton.addEventListener("click", function () {
      var fragment = template.content.cloneNode(true);
      tbody.appendChild(fragment);
      var rows = tbody.querySelectorAll(".mlf-field-row");
      if (rows.length > 0) {
        updateRowPlaceholder(rows[rows.length - 1]);
      }
    });

    tbody.addEventListener("input", function (event) {
      var target = event.target;
      if (!(target instanceof HTMLElement) || !target.classList.contains("mlf-field-key")) {
        return;
      }
      var row = target.closest(".mlf-field-row");
      updateRowPlaceholder(row);
    });

    tbody.addEventListener("click", function (event) {
      var target = event.target;
      if (!(target instanceof HTMLElement)) {
        return;
      }

      if (!target.classList.contains("mlf-remove-row")) {
        return;
      }

      var row = target.closest(".mlf-field-row");
      if (!row) {
        return;
      }

      var rows = tbody.querySelectorAll(".mlf-field-row");
      if (rows.length <= 1) {
        row.querySelectorAll("input[type='text']").forEach(function (input) {
          input.value = "";
        });
        updateRowPlaceholder(row);
        return;
      }

      row.remove();
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
