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
