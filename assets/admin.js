(function () {
  function normalizeFieldKey(value) {
    return String(value || "")
      .trim()
      .replace(/[{}]+/gu, "")
      .replace(/[^\p{L}\p{N}_-]+/gu, "_")
      .replace(/_+/gu, "_")
      .replace(/^_+|_+$/gu, "");
  }

  function getRowType(row) {
    if (!(row instanceof HTMLElement)) {
      return "select";
    }

    var typeInput = row.querySelector(".malifo-field-type");
    if (!(typeInput instanceof HTMLSelectElement) || !typeInput.value) {
      return "select";
    }

    return typeInput.value;
  }

  function shouldShowContentVariant(variantType, rowType) {
    if (variantType === "options") {
      return rowType === "select";
    }

    if (variantType === "value") {
      return rowType === "textarea" || rowType === "text" || rowType === "checkbox";
    }

    return false;
  }

  function syncRowType(row) {
    if (!(row instanceof HTMLElement)) {
      return;
    }

    var rowType = getRowType(row);
    row.setAttribute("data-field-type", rowType);

    row.querySelectorAll(".malifo-field-content-variant").forEach(function (variant) {
      if (!(variant instanceof HTMLElement)) {
        return;
      }

      var variantClass = Array.from(variant.classList).find(function (className) {
        return className.indexOf("malifo-field-content-variant--") === 0;
      });

      if (!variantClass) {
        return;
      }

      var variantType = variantClass.replace("malifo-field-content-variant--", "");
      variant.classList.toggle("is-hidden-by-type", !shouldShowContentVariant(variantType, rowType));
    });
  }

  function updateRowPlaceholder(row) {
    if (!(row instanceof HTMLElement)) {
      return;
    }

    var keyInput = row.querySelector(".malifo-field-key");
    var placeholderInput = row.querySelector(".malifo-placeholder-value");
    if (!(keyInput instanceof HTMLInputElement) || !(placeholderInput instanceof HTMLInputElement)) {
      return;
    }

    var normalized = normalizeFieldKey(keyInput.value);
    placeholderInput.value = normalized ? "{{" + normalized + "}}" : "";
  }

  function updateRowExamples(row) {
    if (!(row instanceof HTMLElement)) {
      return;
    }

    var rowType = getRowType(row);

    row.querySelectorAll(".malifo-field-key, .malifo-field-label, .malifo-field-value, .malifo-field-options").forEach(function (input) {
      if (!(input instanceof HTMLInputElement)) {
        return;
      }

      var datasetKey = "example" + rowType.charAt(0).toUpperCase() + rowType.slice(1);
      var example = input.dataset[datasetKey] || "";
      input.placeholder = example;
    });
  }

  function syncRequiredField(row) {
    if (!(row instanceof HTMLElement)) {
      return;
    }

    var requiredInput = row.querySelector(".malifo-field-required-value");
    var requiredToggle = row.querySelector(".malifo-field-required-toggle");
    if (!(requiredInput instanceof HTMLInputElement) || !(requiredToggle instanceof HTMLInputElement)) {
      return;
    }

    requiredInput.value = requiredToggle.checked ? "1" : "0";
  }

  function syncRow(row) {
    syncRowType(row);
    updateRowExamples(row);
    syncRequiredField(row);
    updateRowPlaceholder(row);
  }

  function updateAllRows(tbody) {
    tbody.querySelectorAll(".malifo-field-row").forEach(syncRow);
  }

  function clearRow(row) {
    if (!(row instanceof HTMLElement)) {
      return;
    }

    row.querySelectorAll(".malifo-field-key, .malifo-field-label, .malifo-field-value, .malifo-field-options").forEach(function (input) {
      if (!(input instanceof HTMLInputElement)) {
        return;
      }

      input.value = "";
    });

    var requiredToggle = row.querySelector(".malifo-field-required-toggle");
    if (requiredToggle instanceof HTMLInputElement) {
      requiredToggle.checked = false;
    }

    syncRow(row);
  }

  function appendRow(tbody, template, fieldType) {
    var fragment = template.content.cloneNode(true);
    var row = fragment.querySelector(".malifo-field-row");
    if (!(row instanceof HTMLElement)) {
      return;
    }

    var typeInput = row.querySelector(".malifo-field-type");
    if (typeInput instanceof HTMLSelectElement) {
      typeInput.value = fieldType || "select";
    }

    syncRow(row);
    tbody.appendChild(fragment);
  }

  function init() {
    var tbody = document.getElementById("malifo-fields-body");
    var template = document.getElementById("malifo-row-template");
    var addButtons = document.querySelectorAll(".malifo-add-row");

    if (!(tbody instanceof HTMLElement) || !(template instanceof HTMLTemplateElement) || addButtons.length === 0) {
      return;
    }

    updateAllRows(tbody);

    addButtons.forEach(function (button) {
      button.addEventListener("click", function () {
        var fieldType = button.getAttribute("data-field-type") || "select";
        appendRow(tbody, template, fieldType);
      });
    });

    tbody.addEventListener("input", function (event) {
      var target = event.target;
      if (!(target instanceof HTMLElement) || !target.classList.contains("malifo-field-key")) {
        return;
      }

      syncRow(target.closest(".malifo-field-row"));
    });

    tbody.addEventListener("change", function (event) {
      var target = event.target;
      if (!(target instanceof HTMLElement)) {
        return;
      }

      if (target.classList.contains("malifo-field-type") || target.classList.contains("malifo-field-required-toggle")) {
        syncRow(target.closest(".malifo-field-row"));
      }
    });

    tbody.addEventListener("click", function (event) {
      var target = event.target;
      if (!(target instanceof HTMLElement) || !target.classList.contains("malifo-remove-row")) {
        return;
      }

      var row = target.closest(".malifo-field-row");
      if (!row) {
        return;
      }

      if (tbody.querySelectorAll(".malifo-field-row").length <= 1) {
        clearRow(row);
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
