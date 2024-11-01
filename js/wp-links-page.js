(function($) {


jQuery(document).ready(function($) {

			function getBase64Image(img) {
					var canvas = document.createElement("canvas");
					canvas.width = img.width;
					canvas.height = img.height;
					var ctx = canvas.getContext("2d");
					ctx.drawImage(img, 0, 0);
					var dataURL = canvas.toDataURL("image/png");
					return dataURL.replace(/^data:image\/(png|jpg);base64,/, "");
			}


			$('#titlediv #title:not(.ss), #title-prompt-text:not(.ss)').remove();


			if ($('#wplp_featured_image').val() == '') {
				$('#publish[value=Publish]').prop('disabled',true);
			}

			$("input[name=wplf_image_type]").change( function () {
			if ($("input[name=wplf_image_type]:checked").val() == "screenshot") {
					$(".screenshot").show();
					var value = $('input[name=wplp_screenshot_refresh]').attr('data-current');
					$('input[name=wplp_screenshot_refresh][value='+value+']').prop('checked',true);
				} else {
					$(".screenshot").hide();
					$('input[name=wplp_screenshot_refresh][value=never]').prop('checked',true);
				}
			});

			function get_hostname(url) {
				var m = url.match(/^http:\/\/[^/]+/);
				return m ? m[0] : null;
			}

			var progressbar = $( "#progressbar" ),
			progressLabel = $( ".progress-label" );

				progressbar.progressbar({
				  value: false,
				  change: function() {
					progressLabel.text( progressbar.progressbar( "value" ) + "%" );
				  },
				  complete: function() {
					progressLabel.text( "Complete!" );
				  }
				});


			var progressbar1 = $( "#progressbar1" ),
			progressLabel1 = $( ".progress-label1" );

				progressbar1.progressbar({
				  value: false,
				  change: function() {
					progressLabel1.text( progressbar1.progressbar( "value" ) + "%" );
				  },
				  complete: function() {
					progressLabel1.text( "Complete!" );
				  }
				});

			$('#update-screenshots').click(function() {
				var ids = $(this).data('total').toString();
				if (ids.indexOf(",") >= 0) {
				var id_arr = ids.split(',');
				} else id_arr = [ids];
				var total = id_arr.length;
				var done = 0;
				$('.update-message').show();
				$('#progressbar').show();

				var val = progressbar.progressbar( "value" ) || 0;

				$.each(id_arr, function(key, id) {
					$.post(ajax_object.ajax_url,{
							"action": "wplf_ajax_update_screenshots",
							"nonce": ajax_object.nonce,
							"id": id,
						}
					)
					.always(function() {
						done = done + 1;
						val = done / total * 100;
						val = Math.round(val);
				 		progressbar.progressbar( "value", val );
					})
					.done(function() {
						$(document).ajaxStop(function() {
							$('#update-screenshots').prop("disabled",false);
							location.reload(true);
							window.location = self.location;
							});
						});

				});
				$('#update-screenshots').prop("disabled",true);
				$('#update-screenshots').html("Please Wait...");
			});

			$('#publish-links').click(function(e) {
				e.preventDefault();
				$(this).html('Saving...Please Wait');
				$.post(ajax_object.ajax_url,{
					"data": $('#wplf-new_post').serialize(),
					"action": "wplf_quick_link",
					"nonce": ajax_object.nonce,
				})
				.done(function(response) {
					//location.reload(true);
					//window.location = self.location;
				})
				.fail(function(response) {
					alert("There was an error saving this link. Please try again or use the Add Link Form.")
				});

			});

		$('#title').change(function() {
			$('#Publish').prop('disabled',true);
			$('.wplp_loading').show();
			$('.wplp_featured').hide();

			var url = $('#title').val();
			if (!$('input#wplp_display').val()) {
				$('input#wplp_display').val(url);
			}
			var url_check = url;
			var done = '';

			if ($('#wplp_screenshot_url').val()) {
				url = $('#wplp_screenshot_url').val();
			}
			var ss_type = $('input[name=wplp_screenshot_size]').val();

			if (url.indexOf("http") < 0 ) {
				url_check = "https://"+url;
				url = "https%3A%2F%2F"+url;
			}

			if (ss_type == 'large') {
				d = new Date();
				var first = 'https://s0.wp.com/mshots/v1/'+url+'?w=1200';
					$('.wplp_featured').attr('src', first);
					$('#wplp_featured_image').val(first);
				setTimeout(function() {
					var img = 'https://s0.wp.com/mshots/v1/'+url+'?w=1280';
					$('.wplp_featured').attr('src', img);
					$('.wplp_loading').hide();
					$('.wplp_featured').show();
					$('#wplp_featured_image').val(img);
					if (done = "done" ) {
						$('#publish[value=Publish]').prop('disabled',false);
					}
				}, 15000);

			} else if(ss_type == 'small') {
				var key = $('.apikey').val();
				$.ajax({
				url: 'https://pagespeedonline.googleapis.com/pagespeedonline/v5/runPagespeed?url=' + url + '&key=' + key,
				type: 'GET',
				dataType: 'json',
				success: function(data) {
					data = data.lighthouseResult.audits['final-screenshot'].details.data;
					//data = data.replace(/_/g, '/').replace(/-/g, '+');
					$('.wplp_featured').attr('src', data);
					$('.wplp_loading').hide();
					$('.wplp_featured').show();
					var img = data.replace('data:image/jpeg;base64,','');
					$('#wplp_featured_image').val(img);
					if (done = "done" ) {
						$('#publish[value=Publish]').prop('disabled',false);
					}
				},
				error: function() {
							alert('There was an error getting the screenshot. Please make sure the url is valid and try again.');
							if (done = "done" ) {
								$('#publish[value=Publish]').prop('disabled',false);
							}
						},
				fail: function(data) {
					alert('Please enter a valid URL.');
					if (done = "done" ) {
						$('#publish[value=Publish]').prop('disabled',false);
					}
				}
			});
			}

			$('#title').val(url_check);

		});

		$('.set-featured-screenshot').click(function(e) {
			e.preventDefault();
			$('.wplp_loading').show();
			$('.wplp_featured').hide();

			$('.set-featured-screenshot').prop("disabled",true);
			$('.set-featured-screenshot').html("Please Wait...");
			var url = $('#title').val();
			if ($('#wplp_screenshot_url').val()) {
				url = $('#wplp_screenshot_url').val();
			}

				var ss_type = $('input[name=wplp_screenshot_size]').val();
				if (url.indexOf("http") < 0 ) {
					url_check = "http://"+url;
					url = "http%3A%2F%2F"+url;
				}

				if (ss_type == 'large') {
					d = new Date();
					var first = 'https://s0.wp.com/mshots/v1/'+url+'?w=1200';
						$('.wplp_featured').attr('src', first);
						$('#wplp_featured_image').val(first);
					setTimeout(function() {
						var img = 'https://s0.wp.com/mshots/v1/'+url+'?w=1280';
						$('.wplp_featured').attr('src', img);
						$('.wplp_loading').hide();
						$('.wplp_featured').show();
						$('#wplp_featured_image').val(img);
						$('#wplp_media_image').val('false');
						$('.set-featured-screenshot').html("Generate New Screenshot");
					}, 15000);

				} else if(ss_type == 'small') {
					var key = $('.apikey').val();
					$.ajax({
					url: 'https://pagespeedonline.googleapis.com/pagespeedonline/v5/runPagespeed?url=' + url + '&key=' + key,
					type: 'GET',
					dataType: 'json',
					success: function(data) {
						data = data.lighthouseResult.audits['final-screenshot'].details.data;
						data = data.replace(/_/g, '/').replace(/-/g, '+');
						$('.wplp_featured').attr('src', data);
						$('.wplp_loading').hide();
						$('.wplp_featured').show();
						var img = data.replace('data:image/jpeg;base64,','');
						$('#wplp_featured_image').val(img);
						$('#wplp_media_image').val('false');
						$('.set-featured-screenshot').html("Generate New Screenshot");
					},
					error: function() {
								alert('There was an error getting the screenshot. Please make sure the url is valid and try again.');
								$('.set-featured-screenshot').html("Generate New Screenshot");
							},
					fail: function(data) {
						$('.set-featured-screenshot').html("Generate New Screenshot");
					}
				});

				}
		});

		var custom_uploader1;

    $('.set-featured-thumbnail').click(function(e) {
        e.preventDefault();
		var set = false;

		if(set == false) {
        //If the uploader object has already been created, reopen the dialog
        if (custom_uploader1) {
            custom_uploader1.open();
            return;
        }

        //Extend the wp.media object
        custom_uploader1 = wp.media.frames.file_frame = wp.media({
            title: 'Replace Screenshot',
            button: {
                text: 'Set Screenshot'
            },
            multiple: false
        });

        //When a file is selected, grab the URL and set it as the text field's value
        custom_uploader1.on('select', function() {
            attachment = custom_uploader1.state().get('selection').first().toJSON();
			$('.wplp_featured').attr('src', attachment.url);
			$('.wplp_featured').show();
			$('#wplp_featured_image').val(attachment.id);
            $('#wplp_media_image').val('true');
        });

        //Open the uploader dialog
        custom_uploader1.open();
		}
	});

	$('#update_wplf').click(function(e) {
        e.preventDefault();
		var ids = $(this).data('total');
		var id_arr = ids.split(',');
		var total = id_arr.length;
		var done = 0;
		$('#progressbar').show();
		var val = progressbar.progressbar( "value" ) || 0;
		$(this).html('Please Wait...');
		var success = 0;
		var error = 0;
		var error_ids = '';
		$.each(id_arr, function(key, id) {
			$.post(ajax_object.ajax_url,{
					"action": "wplf_update_from_previous", 
					"nonce": ajax_object.nonce,
					"id": id,
				}
			)
			.always(function() {
				done = done + 1;
				val = done / total * 100;
				val = Math.round(val);
				progressbar.progressbar( "value", val );
			})
			.done(function() {
				$(document).ajaxStop(function() {
					$("#progressbar").hide();
					if ( error_ids != '') {
					$('#update_wplf').html('Update Links');
					$('p.error.update').html('There were '+error+' error(s) while importing. You will need to manually add these links. The link ids that failed are '+error_ids+'.').show();
					} else if ( error_ids == '') {
					$('p.success').html('All Links were imported successfully.').show();
					$('#update_wplf').html('Update Links');
					}
				});
			})
			.fail(function() {
				error++;
				if (error_ids === '') {
					error_ids = id;
				} else {
					error_ids = error_ids+', '+id;
				}
			});
		});

	});








});


})(jQuery);
