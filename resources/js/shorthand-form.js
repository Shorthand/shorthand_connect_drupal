const storyFilter = document.getElementById('story_filter');
const storyRows = [
  ...document.querySelectorAll('.shorthand-story-list tbody tr'),
];

const stories = storyRows.map((row, index) => {
  const children = row.children;
  return {
    index,
    element: row,
    id: children[1].innerHTML,
    title: children[2].innerHTML,
    status: children[3].innerHTML,
  };
});

function filterStories(filter) {
  stories.forEach((story) => {
    story.element.classList.remove('hide');
    const hide =
      story.id.toLowerCase().indexOf(filter) < 0 &&
      story.title.toLowerCase().indexOf(filter) < 0 &&
      story.status.toLowerCase().indexOf(filter) < 0;
    if (hide) {
      story.element.classList.add('hide');
    }
  });
}

if (storyFilter) {
  storyFilter.addEventListener('keyup', () => {
    const filter = storyFilter.value;
    filterStories(filter.toLowerCase());
  });
}

document.addEventListener('change', (event) => {
  const selectAll = event.target.closest('.shorthand-version-delete-form .shorthand-select-all');
  if (!selectAll) {
    return;
  }

  const form = selectAll.closest('form');
  if (!form) {
    return;
  }

  const options = form.querySelectorAll('.shorthand-version-options input[type="checkbox"]');
  options.forEach((option) => {
    if (!option.disabled) {
      option.checked = selectAll.checked;
    }
  });
});
