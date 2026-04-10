(function () {
  function isWithin(codePoint, start, end) {
    return codePoint >= start && codePoint <= end;
  }

  function hasCaseVariant(character) {
    return character.toLowerCase() !== character.toUpperCase();
  }

  function isAllowedFieldChar(character) {
    if (character === "_" || character === "-") {
      return true;
    }

    if (/\s/.test(character)) {
      return false;
    }

    if (/[!"#$%&'()*+,./:;<=>?@[\\\]^`{|}~、。・「」『』【】（）［］｛｝〈〉《》〔〕！？：；，．…]/.test(character)) {
      return false;
    }

    var normalized = typeof character.normalize === "function" ? character.normalize("NFKC") : character;
    if (/^[A-Za-z0-9]$/.test(normalized)) {
      return true;
    }

    if (hasCaseVariant(character) || hasCaseVariant(normalized)) {
      return true;
    }

    var codePoint = character.codePointAt(0);

    return (
      isWithin(codePoint, 0x00c0, 0x024f) ||
      isWithin(codePoint, 0x1e00, 0x1eff) ||
      isWithin(codePoint, 0x3040, 0x309f) ||
      codePoint === 0x3005 ||
      codePoint === 0x3006 ||
      codePoint === 0x303b ||
      isWithin(codePoint, 0x30a0, 0x30ff) ||
      isWithin(codePoint, 0x31f0, 0x31ff) ||
      isWithin(codePoint, 0x3400, 0x4dbf) ||
      isWithin(codePoint, 0x4e00, 0x9fff) ||
      isWithin(codePoint, 0xf900, 0xfaff) ||
      isWithin(codePoint, 0xac00, 0xd7af) ||
      isWithin(codePoint, 0xff10, 0xff19) ||
      isWithin(codePoint, 0xff21, 0xff3a) ||
      isWithin(codePoint, 0xff41, 0xff5a) ||
      isWithin(codePoint, 0xff66, 0xff9f)
    );
  }

  function normalizeFieldKey(value) {
    var input = String(value || "").trim().replace(/[{}]+/g, "");
    var normalized = "";

    Array.from(input).forEach(function (character) {
      normalized += isAllowedFieldChar(character) ? character : "_";
    });

    return normalized.replace(/_+/g, "_").replace(/^_+|_+$/g, "");
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

  function shouldShowCell(cellType, rowType) {
    if (cellType === "value") {
      return rowType === "textarea" || rowType === "text" || rowType === "checkbox";
    }

    if (cellType === "placeholder-setting") {
      return rowType === "textarea" || rowType === "text";
    }

    if (cellType === "options") {
      return rowType === "select";
    }

    return true;
  }

  function syncRowType(row) {
    if (!(row instanceof HTMLElement)) {
      return;
    }

    var rowType = getRowType(row);
    row.setAttribute("data-field-type", rowType);

    row.querySelectorAll(".malifo-field-cell").forEach(function (cell) {
      if (!(cell instanceof HTMLElement)) {
        return;
      }

      var fieldClass = Array.from(cell.classList).find(function (className) {
        return className.indexOf("malifo-field-cell--") === 0;
      });

      if (!fieldClass) {
        return;
      }

      var cellType = fieldClass.replace("malifo-field-cell--", "");
      cell.classList.toggle("is-hidden-by-type", !shouldShowCell(cellType, rowType));
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

  function syncRow(row) {
    syncRowType(row);
    updateRowPlaceholder(row);
  }

  function updateAllRows(tbody) {
    tbody.querySelectorAll(".malifo-field-row").forEach(syncRow);
  }

  function clearRow(row) {
    if (!(row instanceof HTMLElement)) {
      return;
    }

    row.querySelectorAll("input[type='text'], textarea").forEach(function (input) {
      input.value = "";
    });

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
      if (!(target instanceof HTMLElement) || !target.classList.contains("malifo-field-type")) {
        return;
      }

      syncRow(target.closest(".malifo-field-row"));
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
