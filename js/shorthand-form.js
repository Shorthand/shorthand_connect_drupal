let stories = [];
document.addEventListener("DOMContentLoaded", function () {
  // store stories as an object
  stories = JSON.parse(
    document.querySelector("#shorthand-stories-data").innerHTML
  );
  //remove element
  document.querySelector("#shorthand-stories-data").remove();

  // add change listener to dropdown
  document
    .querySelector("[name*='shorthand_id']")
    .addEventListener("change", function (e) {
      populateFields(e.target.value);
    });
  // prepopulate initially selected story if new
  if (!window.location.pathname.includes("/edit")) {
    populateFields(document.querySelector("[name*='shorthand_id']").value);
  }

  // add change listener to external assets checkbox
  let publishConfigDiv = document.querySelector(
    "#edit-external-publishing-config-wrapper"
  );

  const actions = document.querySelector("#edit-actions");
  const loader = document.createElement("div");
  loader.classList.add("loader");
  actions.prepend(loader);

  if (!document.querySelector("[name*='external_assets']").checked) {
    publishConfigDiv.classList.add("hide");
  }
  document
    .querySelector("[name*='external_assets']")
    .addEventListener("change", function (e) {
      if (e.target.checked) {
        publishConfigDiv.classList.remove("hide");
      } else {
        publishConfigDiv.classList.add("hide");
      }
    });
  setTimeout(() => {
    publishConfigDiv.classList.add("transition");
  }, 10);
  document
    .querySelector("#edit-submit")
    .addEventListener("click", function (e) {
      const submit = document.querySelector("#edit-submit");
      submit.value = "  Saving...";
      loader.classList.add("show");

      setTimeout(() => {
        document.querySelectorAll("input, textarea").forEach(function (input) {
          input.disabled = true;
        });
      });
    });
});

function populateFields(id) {
  if (stories.length === 0) {
    return;
  }
  const story = stories.find((s) => s.id === id);
  const form = document.querySelector("#shorthand-story-add-form");
  form.querySelector("input[name*='name']").value = story.title;
  form.querySelector("input[name*='thumbnail']").value = story.image;
  form.querySelector("input[name*='authors']").value = story.metadata.authors;
  form.querySelector("input[name*='keywords']").value = story.metadata.keywords;
  form.querySelector("input[name*='description']").value =
    story.metadata.description;
  form.querySelector("input[name*='external_url']").value = story.external_url;
}
