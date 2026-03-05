(function () {
  function init() {
    var addButton = document.getElementById("mlf-add-row");
    var tbody = document.getElementById("mlf-fields-body");
    var template = document.getElementById("mlf-row-template");

    if (!addButton || !tbody || !template) {
      return;
    }

    addButton.addEventListener("click", function () {
      var fragment = template.content.cloneNode(true);
      tbody.appendChild(fragment);
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
        row.querySelectorAll("input").forEach(function (input) {
          input.value = "";
        });
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

