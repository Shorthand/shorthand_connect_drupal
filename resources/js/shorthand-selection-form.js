const storyData = JSON.parse(
  document.getElementById('shorthand-stories-data').innerHTML,
).reduce((a, v) => {
  return Object.assign(a, {
    [v.id]: v,
  });
}, {});

const fieldWrapper = document.getElementById(
  'edit-field-shorthand-story-wrapper',
);

const storyFilter = document.createElement('input');
storyFilter.id = 'story_filter';
storyFilter.classList.add('form-element');
storyFilter.placeholder = 'Filter stories';
fieldWrapper.appendChild(storyFilter);

const storySelectField = document.querySelector(
  'select[id^=edit-field-shorthand-story-]',
);
const initSelected = storySelectField.value || 0;
storySelectField.classList.add('hide');

const storyTitleField = document.querySelector('input[id^=edit-title-]');

const options = [...storySelectField.options]
  .reduce((a, option) => {
    if (option.value === '0') {
      return a;
    }
    const storyId = option.value.split('/')[0];
    const version = option.value.split('/')[1];
    a.push([storyId, version]);
    return a;
  }, [])
  .sort()
  .reverse();

const visualOptions = {};

options.forEach((option) => {
  if (option) {
    const storyId = option[0];
    const version = option[1];

    if (!visualOptions[storyId]) {
      visualOptions[storyId] = {
        ...storyData[storyId],
        versions: [version],
      };
    } else {
      visualOptions[storyId].versions.push(version);
    }
  }
});

function updateSelection(newStoryId, newVersion) {
  const storyId = newStoryId || initSelected.split('/')[0];
  const version = newVersion || initSelected.split('/')[1];

  Object.keys(visualOptions).forEach((key) => {
    const story = visualOptions[key];
    story.element.classList.remove('selected');
    if (story.id === storyId) {
      story.element.classList.add('selected');
    }

    story.versions.forEach((v) => {
      document.getElementById(`${story.id}/${v}`).classList.remove('selected');
      if (v === version && story.id === storyId) {
        document.getElementById(`${story.id}/${v}`).classList.add('selected');
      }
    });
  });

  if (newStoryId) {
    storyTitleField.value = visualOptions[storyId].title;
    storySelectField.value = `${newStoryId}/${newVersion}`;
  }
}

const shorthandStoriesWrapper = document.createElement('div');
shorthandStoriesWrapper.className = 'shorthand-stories-wrapper';
fieldWrapper.appendChild(shorthandStoriesWrapper);

Object.keys(visualOptions).forEach((key) => {
  const visualOption = visualOptions[key];
  // Create div for each story, and a list for the versions with proper time/date
  const storyWrapper = document.createElement('div');
  storyWrapper.className = 'shorthand-story';
  storyWrapper.innerHTML = `
    <img src="${visualOption.image}"/>
    <div class="story-details">
      <h3>${visualOption.title}</h3>
      <p>${visualOption.metadata.description}</p>
      <div class="story-versions" id="versions-${visualOption.id}">
        <span>Versions</span>
      </div>
    </div>
  `;

  shorthandStoriesWrapper.appendChild(storyWrapper);
  storyWrapper.addEventListener('click', () => {
    updateSelection(visualOption.id, visualOption.versions[0]);
  });
  visualOption.element = storyWrapper;

  const versionsWrapper = document.getElementById(
    `versions-${visualOption.id}`,
  );

  visualOption.versions.forEach((version) => {
    const versionOption = document.createElement('div');
    versionOption.id = `${visualOption.id}/${version}`;
    versionOption.className = 'story-version-option';
    versionOption.dataset.option = `${visualOption.id}/${version}`;
    versionOption.innerHTML = new Date(version).toLocaleString();

    versionsWrapper.appendChild(versionOption);

    versionOption.addEventListener('click', (e) => {
      updateSelection(visualOption.id, version);
      e.stopPropagation();
    });
  });
});

function filterStories(filter) {
  Object.keys(visualOptions).forEach((key) => {
    const story = visualOptions[key];
    story.element.classList.remove('hide');
    const hide =
      story.id.toLowerCase().indexOf(filter) < 0 &&
      story.title.toLowerCase().indexOf(filter) < 0 &&
      story.metadata.description.toLowerCase().indexOf(filter) < 0 &&
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

updateSelection();
