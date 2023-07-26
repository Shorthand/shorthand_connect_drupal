const story_data = JSON.parse(
  document.getElementById("shorthand-stories-data").innerHTML
).reduce((a, v) => {
  return Object.assign(a, {
    [v.id]: v,
  });
}, {});

const field_wrapper = document.getElementById(
  "edit-field-shorthand-story-wrapper"
);

const story_filter = document.createElement("input");
story_filter.id = "story_filter";
story_filter.classList.add("form-element");
story_filter.placeholder = "Filter stories";
field_wrapper.appendChild(story_filter);

const story_select_field = document.querySelector(
  "select[id^=edit-field-shorthand-story-]"
);
const init_selected = story_select_field.value || 0;
story_select_field.classList.add("hide");

const story_title_field = document.querySelector("input[id^=edit-title-]");

const options = [...story_select_field.options]
  .reduce((a, option) => {
    if (option.value == 0) return a;
    const story_id = option.value.split("/")[0];
    const version = option.value.split("/")[1];
    a.push([story_id, version]);
    return a;
  }, [])
  .sort()
  .reverse();

const visual_options = {};

for (let option of options) {
  if (option) {
    const story_id = option[0];
    const version = option[1];

    if (!visual_options[story_id]) {
      visual_options[story_id] = {
        ...story_data[story_id],
        versions: [version],
      };
    } else {
      visual_options[story_id].versions.push(version);
    }
  }
}

const shorthand_stories_wrapper = document.createElement("div");
shorthand_stories_wrapper.className = "shorthand-stories-wrapper";
field_wrapper.appendChild(shorthand_stories_wrapper);

for (let key of Object.keys(visual_options)) {
  const visual_option = visual_options[key];
  //create div for each story, and a list for the versions with proper time/date
  const story_wrapper = document.createElement("div");
  story_wrapper.className = "shorthand-story";
  story_wrapper.innerHTML =
    '<img src="' +
    visual_option.image +
    '"/><div class="story-details">' +
    "<h3>" +
    visual_option.title +
    "</h3><p>" +
    visual_option.metadata.description +
    "</p>" +
    "<div class='story-versions' id='versions-" +
    visual_option.id +
    "'><span>Versions</span></div>";
  ("</div>");

  shorthand_stories_wrapper.appendChild(story_wrapper);
  story_wrapper.addEventListener("click", () => {
    updateSelection(visual_option.id, visual_option.versions[0]);
  });
  visual_option.element = story_wrapper;

  const versions_wrapper = document.getElementById(
    "versions-" + visual_option.id
  );
  for (let version of visual_option.versions) {
    const version_option = document.createElement("div");
    version_option.id = visual_option.id + "/" + version;
    version_option.className = "story-version-option";
    version_option.dataset.option = visual_option.id + "/" + version;
    version_option.innerHTML = new Date(version).toLocaleString();

    versions_wrapper.appendChild(version_option);

    version_option.addEventListener("click", (e) => {
      updateSelection(visual_option.id, version);
      e.stopPropagation();
    });
  }
}

story_filter.addEventListener("keyup", () => {
  const filter = story_filter.value;
  filterStories(filter.toLowerCase());
});

function filterStories(filter) {
  for (let key of Object.keys(visual_options)) {
    const story = visual_options[key];
    story.element.classList.remove("hide");
    const hide =
      story.id.toLowerCase().indexOf(filter) < 0 &&
      story.title.toLowerCase().indexOf(filter) < 0 &&
      story.metadata.description.toLowerCase().indexOf(filter) < 0 &&
      story.status.toLowerCase().indexOf(filter) < 0;
    if (hide) {
      story.element.classList.add("hide");
    }
  }
}

function updateSelection(new_story_id, new_version) {
  let story_id = new_story_id || init_selected.split("/")[0];
  let version = new_version || init_selected.split("/")[1];

  for (let key of Object.keys(visual_options)) {
    const story = visual_options[key];
    story.element.classList.remove("selected");
    if (story.id == story_id) {
      story.element.classList.add("selected");
    }

    for (let v of story.versions) {
      document.getElementById(story.id + "/" + v).classList.remove("selected");
      if (v == version && story.id == story_id) {
        document.getElementById(story.id + "/" + v).classList.add("selected");
      }
    }
  }

  if (new_story_id) {
    story_title_field.value = visual_options[story_id].title;
    story_select_field.value = new_story_id + "/" + new_version;
  }
}

updateSelection();
