jQuery(document).ready(function ($) {
  $("#remove-images").on("click", function (e) {
    e.preventDefault();

    var post_id = $("#post_ID").val();

    $.ajax({
      url: ajax_object.ajax_url,
      type: "post",
      data: {
        action: "remove_images",
        post_id: post_id,
      },
      success: function (response) {
        if (response.success) {
          $("#output").text(response.data);
        } else {
          $("#output").text("Image Not Deleted  ");
        }
      },
    });
  });

  $("#remove-images-media").on("click", function (e) {
    e.preventDefault();
    var attachment_id = $("#post_ID").val();
    $.ajax({
      url: ajax_object.ajax_url,
      type: "post",
      data: {
        action: "remove_images_media",
        attachment_id: attachment_id,
      },
      success: function (response) {
        if (response.success) {
          $("#output-media").text(response.data);
        } else {
          $("#output-media").text("Image Not Deleted");
        }
      },
    });
  });
});
