import 'bootstrap/dist/css/bootstrap.min.css';
require('../css/main.css');
require('../css/admin.css');
require('./app');

import jQuery from 'jquery';
import icons from './icons';

(function(App, $, icons){
	$('.slideshow').on('click', 'a.tag', function (e) {
            e.preventDefault();

            var $button = $(this),
                thumbnail = App.Main.getThumbnail(),
                $thumbnail = $(thumbnail),
                tagHash = $(this).data('hash'),
                picHash = $thumbnail.data('picture'),
                tagRegex = new RegExp('\\b'+tagHash+',?'),
                tags = $thumbnail.data('tags') + '';

            if (tagRegex.test(tags)) {
                $thumbnail.data('tags', tags.replace(tagRegex, ''));
                status = 0;
            } else {
                $thumbnail.data('tags', (tags == '') ? tagHash : tags + ',' + tagHash);
                status = 1;
            }

            $(this).removeClass('btn-light btn-success').addClass('btn-default');

            $.ajax({
                type : 'POST',
                url  : $(this).attr('href').replace('XXX', picHash).replace('YYY', status)
            })
                .then(
                    state => {
                        // Picture might have changed
                        if ($thumbnail.data('picture') === picHash) {
                            state = parseInt(state);
                            $button
                                .removeClass('btn-default')
                                .addClass('btn-'+(state ? 'success' : 'light'))
                        }
                    },
                    error => {
                        if ($thumbnail.data('picture') === picHash) {
                            $button
                                .addClass('btn-danger')
                                .removeClass('btn-default');
                        }
                    })
        })

	$('a.delete').click(function(e) {
		e.preventDefault();
		// todo: call API remove
		$(this).parents('.photo-container').remove();

		$.get($(this).attr('href'));
	});

	$('select[name="import"]').change(function (e) {
		if ($('input[name="folder"]').val() === '' || $('input[name="name"]').val() === '') {
			$('input[name="name"]').val($(this).val().replace(/^.*\//, ''));
			$('input[name="folder"]').val($(this).val().replace(/^.*\//, '').replace(/[^0-9a-zA-Z_\-]+/g, '_').toLowerCase());
		}
	});
})(window.App = window.App || {}, jQuery, icons);