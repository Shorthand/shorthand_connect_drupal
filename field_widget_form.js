/**
 * @file
 */

jQuery(document).ready(function () {
  var currentVersion = shStoryData["version"];
  var showArchivedStories = false;
  var $shorthandStoryDiv = jQuery(".field-name-shorthand-story-id");

  $shorthandStoryDiv.each(function () {
    var existingID = jQuery(this).find("input[type=text]").val();
    var foundValidStory = existingID ? false : true;
    if (jQuery(this).find("ul.stories")) {
      jQuery(this).append(
        '<div class="filter">Filter: <input type="search" id="storyFilter" placeholder="Example: The Shaving of Yak\'s"></input></div><ul class="stories"></ul>'
      );
      var list = jQuery(this).find("ul.stories");
      jQuery(this).append('<div class="clear"></div>');
      for (var shStory in shStoryData["stories"]) {
        var data = shStoryData["stories"][shStory];
        var archivedMessage = "";
        if (
          shStoryData["version"] !== "v2" &&
          currentVersion === "v2" &&
          !showArchivedStories
        ) {
          continue;
        }

        var serverURL = shStoryData["serverURL"];
        var imageURL = data.image;
        if (shStoryData["version"] !== "v2") {
          imageURL = serverURL + data.image;
          if (currentVersion === "v2") {
            archivedMessage = " (archived)";
          }
        }

        var selected = "";
        var storySelected = "";
        if (existingID && existingID == data.id) {
          selected = "checked";
          storySelected = "selected";
          foundValidStory = true;
        }
        list.append(
          '<li class="story ' +
            storySelected +
            '"><label><input name="story_id" type="radio" value="' +
            data.id +
            '" ' +
            selected +
            ' /><div class="thumbnail" style="background-image:url(' +
            imageURL +
            ')"></div><span>' +
            data.title +
            archivedMessage +
            "</span><div id='data' style='display:none'>" +
            // JSON string of all data for the story
            JSON.stringify(data) +
            "</div></a></label></li>"
        );
      }
    }
    if (!foundValidStory || !shStoryData["stories"]) {
      jQuery(this)
        .find("ul.stories")
        .html(
          '<div class="story_not_found"><h3>Could not find this story to edit, cannot update!  Updating disabled.</h3><p>Please check that you are using the correct API version, and that the story exists in Shorthand and try again.</p></div>'
        );
      jQuery("#edit-submit").prop("disabled", true);
    }
  });

  jQuery("li.story input:radio").click(function () {
    jQuery("li.story").removeClass("selected");
    jQuery(this).parent().parent().addClass("selected");
    jQuery("label#title-prompt-text").text("");
    var input = jQuery(this)
      .parent()
      .parent()
      .parent()
      .parent()
      .find("input[type=text]");
    input.val(jQuery(this).val());

    // extract story data and convert to object.
    var story = JSON.parse(jQuery(this).parent().find("#data")[0].innerHTML);

    // store the form Jquery object so we don't keep traversing the DOM
    var form = jQuery("#shorthand-story-node-form");

    form.find("input[name='title']").val(story.title);
    form.find("input[name*='shorthand_story_thumbnail']").val(story.image);
    form
      .find("input[name*='shorthand_story_description']")
      .val(story.metadata.description);
    form
      .find("input[name*='shorthand_story_keywords']")
      .val(story.metadata.keywords);
    form
      .find("input[name*='shorthand_story_authors']")
      .val(story.metadata.authors);
  });

  var input = jQuery("#storyFilter");
  input.on("keyup", function () {
    filter(input.val());
  });

  var stories = jQuery(".stories .story");
  function filter(search = "") {
    search = search.toLowerCase();
    if (search == "") {
      //clear search
      stories.removeClass("hidden");
    } else {
      //hide items
      jQuery(stories).each(function () {
        if (
          jQuery(this).find("span").html().toLowerCase().indexOf(search) < 0
        ) {
          jQuery(this).addClass("hidden");
        } else {
          jQuery(this).removeClass("hidden");
        }
      });
    }
  }
});
