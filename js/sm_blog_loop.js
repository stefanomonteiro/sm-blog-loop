document.addEventListener("DOMContentLoaded", function () {
  //   ! Isotope
  var elem = document.querySelector(
    ".sm_blog-loop.sm_has-filter .sm_blog-loop--grid"
  );
  var buttons = document.querySelectorAll(".sm_blog-loop--filter li");

  var iso = new Isotope(elem, {
    // options
    itemSelector: ".sm_blog-loop--grid_item",
    layoutMode: "fitRows",
  });

  buttons.forEach((button) => {
    button.addEventListener("click", (e) => {
      // only work with buttons
      if (!matchesSelector(e.target, "li")) {
        return;
      }
      var filterValue = e.target.getAttribute("data-filter");
      iso.arrange({ filter: filterValue });
    });
  });
});
