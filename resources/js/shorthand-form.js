const story_filter = document.getElementById("story_filter");
const story_rows = [
  ...document.querySelectorAll(".shorthand-story-list tbody tr"),
];

const stories = story_rows.map((row, index) => {
  let children = row.children;
  return {
    index,
    element: row,
    id: children[1].innerHTML,
    title: children[2].innerHTML,
    status: children[3].innerHTML,
  };
});

story_filter.addEventListener("keyup", () => {
  const filter = story_filter.value;
  filterStories(filter.toLowerCase());
});

function filterStories(filter) {
  for (let story of stories) {
    story.element.classList.remove("hide");
    const hide =
      story.id.toLowerCase().indexOf(filter) < 0 &&
      story.title.toLowerCase().indexOf(filter) < 0 &&
      story.status.toLowerCase().indexOf(filter) < 0;
    if (hide) {
      story.element.classList.add("hide");
    }
  }
}
