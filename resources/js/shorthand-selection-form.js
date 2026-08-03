(function (Drupal, once, $) {
  const STORY_ID_PATTERN = /\(([A-Za-z0-9_-]+)\)\s*$|^([A-Za-z0-9_-]+)$/;

  function extractStoryId(value) {
    const match = value.trim().match(STORY_ID_PATTERN);
    return match ? match[1] || match[2] : '';
  }

  Drupal.behaviors.shorthandLocalStorySelection = {
    attach(context) {
      once(
        'shorthand-local-story',
        '[data-shorthand-local-story-widget]',
        context,
      ).forEach((widget) => {
        const storyInput = widget.querySelector(
          'input[data-shorthand-story-input]',
        );
        const preview = widget.querySelector('[data-shorthand-story-preview]');
        const optionsList = widget.querySelector(
          '[data-shorthand-story-options]',
        );
        const detailUrlTemplate = widget.dataset.shorthandDetailUrl;
        const searchUrl = widget.dataset.shorthandSearchUrl;
        const previewUrlTemplate = widget.dataset.shorthandPreviewUrl;
        if (!storyInput || !preview || !detailUrlTemplate) {
          return;
        }

        let renderedKey = null;
        let lastStoryId = extractStoryId(storyInput.value);
        const detailCache = {};

        // Keep the node title in sync with the selected story, but never
        // overwrite a manually entered title: only fill when the field is
        // empty, still holds the value we auto-filled, or already matches
        // the story title (which also arms syncing on edit forms).
        let autoTitle = null;

        const maybeAutofillTitle = (story) => {
          const titleField = document.querySelector('input[id^="edit-title-"]');
          if (!titleField) {
            return;
          }
          const current = titleField.value.trim();
          if (current === '' || current === autoTitle || current === story.title) {
            titleField.value = story.title;
            autoTitle = story.title;
          }
        };

        // Render the story itself in an iframe under a "Preview" subtitle.
        const renderPreview = (story, versionId) => {
          preview.innerHTML = '';
          if (!story || !previewUrlTemplate || !versionId) {
            return;
          }

          const subtitle = document.createElement('div');
          subtitle.className = 'shorthand-story-preview-subtitle';
          subtitle.textContent = Drupal.t('Preview');
          preview.appendChild(subtitle);

          const frame = document.createElement('iframe');
          frame.className = 'shorthand-story-preview-frame';
          frame.title = story.title;
          frame.loading = 'lazy';
          frame.src = previewUrlTemplate
            .replace('_ID_', encodeURIComponent(story.id))
            .replace('_VERSION_', encodeURIComponent(versionId));
          preview.appendChild(frame);
        };

        // The version currently chosen in the (AJAX-replaced) select;
        // an empty value means the latest downloaded version.
        const getSelectedVersion = (story) => {
          const select = widget.querySelector('select');
          const value = select ? select.value : '';
          if (value && story.versions.some((v) => v.id === value)) {
            return value;
          }
          return story.versions.length ? story.versions[0].id : '';
        };

        const update = () => {
          const storyId = extractStoryId(storyInput.value);
          if (!storyId) {
            if (renderedKey !== '') {
              renderedKey = '';
              renderPreview(null, '');
            }
            return;
          }

          const proceed = (story) => {
            if (!story) {
              renderedKey = '';
              renderPreview(null, '');
              return;
            }
            maybeAutofillTitle(story);
            const version = getSelectedVersion(story);
            const key = `${story.id}/${version}`;
            if (key === renderedKey) {
              return;
            }
            renderedKey = key;
            renderPreview(story, version);
          };

          if (detailCache[storyId]) {
            proceed(detailCache[storyId]);
            return;
          }

          fetch(detailUrlTemplate.replace('_ID_', encodeURIComponent(storyId)))
            .then((response) => (response.ok ? response.json() : null))
            .then((story) => {
              if (story) {
                detailCache[storyId] = story;
              }
              proceed(story);
            })
            .catch(() => {
              proceed(null);
            });
        };

        const highlightSelected = (storyId) => {
          if (!optionsList) {
            return;
          }
          optionsList
            .querySelectorAll('.shorthand-story-option')
            .forEach((row) => {
              row.classList.toggle('selected', row.dataset.storyId === storyId);
            });
        };

        // Settle the current input value: refresh the version select (via
        // the widget's #ajax custom event) only when a story was actually
        // selected or cleared — plain search text must not trigger form
        // rebuilds — then update highlight and preview.
        const settleSelection = () => {
          const value = storyInput.value.trim();
          const isSelection =
            value === '' || /\([A-Za-z0-9_-]+\)\s*$/.test(value);
          const storyId = extractStoryId(value);
          if (isSelection && storyId !== lastStoryId) {
            lastStoryId = storyId;
            if ($) {
              $(storyInput).trigger('shorthandStoryChange');
            }
          }
          highlightSelected(storyId);
          update();
        };

        // Visible options list below the search field: the most recently
        // downloaded/updated stories initially, replaced by keyword matches
        // while searching.
        let searchTimer = null;
        let listRequest = 0;

        const renderOptions = (stories, keyword) => {
          optionsList.innerHTML = '';

          const caption = document.createElement('div');
          caption.className = 'shorthand-story-options-caption';
          caption.textContent =
            keyword === ''
              ? Drupal.t('Recently downloaded stories')
              : Drupal.t('Matching stories');
          optionsList.appendChild(caption);

          if (!stories.length) {
            const empty = document.createElement('div');
            empty.className = 'shorthand-story-options-empty';
            empty.textContent = Drupal.t('No matching downloaded stories.');
            optionsList.appendChild(empty);
            return;
          }

          stories.forEach((story) => {
            const row = document.createElement('button');
            row.type = 'button';
            row.className = 'shorthand-story-option';
            row.dataset.storyId = story.id;

            const thumbnail = document.createElement('img');
            thumbnail.className = 'story-option-thumbnail';
            thumbnail.alt = '';
            thumbnail.src = story.image || story.thumbnail || '';
            if (story.image && story.thumbnail) {
              thumbnail.addEventListener(
                'error',
                () => {
                  thumbnail.src = story.thumbnail;
                },
                { once: true },
              );
            }
            row.appendChild(thumbnail);

            const title = document.createElement('span');
            title.className = 'story-option-title';
            title.textContent = story.title;
            row.appendChild(title);

            if (story.updated) {
              const updated = document.createElement('span');
              updated.className = 'story-option-updated';
              updated.textContent = story.updated;
              row.appendChild(updated);
            }

            row.addEventListener('click', () => {
              storyInput.value = `${story.title} (${story.id})`;
              settleSelection();
            });

            optionsList.appendChild(row);
          });

          highlightSelected(extractStoryId(storyInput.value));
        };

        // Show a loading state only when the request is actually slow
        // (> 200ms), so fast cache-backed responses do not flicker; the
        // current rows stay visible but dimmed while new results load.
        let loadingTimer = null;

        const loadOptions = (keyword) => {
          const requestId = ++listRequest;
          clearTimeout(loadingTimer);
          loadingTimer = setTimeout(() => {
            optionsList.classList.add('is-loading');
            if (!optionsList.querySelector('.shorthand-story-option')) {
              optionsList.innerHTML = '';
              const caption = document.createElement('div');
              caption.className = 'shorthand-story-options-caption';
              caption.textContent = Drupal.t('Loading stories…');
              optionsList.appendChild(caption);
            }
          }, 200);

          const settle = () => {
            clearTimeout(loadingTimer);
            optionsList.classList.remove('is-loading');
          };

          const url = new URL(searchUrl, window.location.origin);
          url.searchParams.set('q', keyword);
          fetch(url)
            .then((response) => (response.ok ? response.json() : []))
            .then((stories) => {
              if (requestId === listRequest) {
                settle();
                renderOptions(stories || [], keyword);
              }
            })
            .catch(() => {
              if (requestId === listRequest) {
                settle();
              }
            });
        };

        if (optionsList && searchUrl) {
          storyInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
              // A selected "Title (id)" value is not a search keyword; plain
              // text (including a raw story ID) is.
              const value = storyInput.value.trim();
              loadOptions(/\([A-Za-z0-9_-]+\)\s*$/.test(value) ? '' : value);
            }, 250);
          });
          loadOptions('');
        }

        storyInput.addEventListener('change', () => settleSelection());

        // Re-render the preview when the (AJAX-replaced) version select
        // changes; delegated because the select element is swapped out.
        widget.addEventListener('change', (event) => {
          if (event.target.matches('select')) {
            update();
          }
        });

        // Show the preview for an existing selection on page load.
        update();
      });
    },
  };
})(Drupal, once, window.jQuery);
