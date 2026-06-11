/* globals wp */
( function () {
    'use strict';

    // -------------------------------------------------------------------------
    // 1. Media Picker (single image — Favicon, OG Image)
    // -------------------------------------------------------------------------
    document.querySelectorAll( '.hpcms-media-picker' ).forEach( function ( btn ) {
        btn.addEventListener( 'click', function () {
            const targetId  = btn.dataset.target;
            const previewId = btn.dataset.preview;
            const title     = btn.dataset.title || 'Select Image';

            const frame = wp.media( {
                title:    title,
                button:   { text: 'Use this image' },
                multiple: false,
                library:  { type: 'image' },
            } );

            frame.on( 'select', function () {
                const attachment = frame.state().get( 'selection' ).first().toJSON();
                document.getElementById( targetId ).value = attachment.id;

                const preview = document.getElementById( previewId );
                preview.innerHTML = '<img src="' + attachment.url + '" style="max-width:300px;height:auto;" />';

                // Show the Remove button if it exists.
                const removeBtn = preview.parentNode.querySelector( '.hpcms-media-remove' );
                if ( removeBtn ) {
                    removeBtn.style.display = '';
                }
            } );

            frame.open();
        } );
    } );

    // -------------------------------------------------------------------------
    // 2. Media Remove Button
    // -------------------------------------------------------------------------
    document.querySelectorAll( '.hpcms-media-remove' ).forEach( function ( btn ) {
        btn.addEventListener( 'click', function () {
            document.getElementById( btn.dataset.target ).value = '';
            document.getElementById( btn.dataset.preview ).innerHTML = '';
        } );
    } );

    // -------------------------------------------------------------------------
    // 3. Gallery Picker (multiple images — Hero images, v1.2.0+)
    // -------------------------------------------------------------------------
    document.querySelectorAll( '.hpcms-gallery-picker' ).forEach( function ( btn ) {
        btn.addEventListener( 'click', function () {
            const previewId = btn.dataset.preview;
            const inputName = btn.dataset.name;

            const frame = wp.media( {
                title:    'Select Images',
                button:   { text: 'Add to Gallery' },
                multiple: true,
                library:  { type: 'image' },
            } );

            frame.on( 'select', function () {
                const preview   = document.getElementById( previewId );
                const selection = frame.state().get( 'selection' );

                selection.each( function ( attachment ) {
                    const att   = attachment.toJSON();
                    const thumb = document.createElement( 'div' );
                    thumb.className = 'hpcms-gallery-thumb';
                    thumb.dataset.id = att.id;
                    thumb.innerHTML =
                        '<img src="' + att.url + '" />' +
                        '<input type="hidden" name="' + inputName + '" value="' + att.id + '" />' +
                        '<button type="button" class="hpcms-remove-gallery-item">\u2715</button>';
                    preview.appendChild( thumb );
                    bindRemoveGalleryItem( thumb.querySelector( '.hpcms-remove-gallery-item' ) );
                } );
            } );

            frame.open();
        } );
    } );

    function bindRemoveGalleryItem( btn ) {
        btn.addEventListener( 'click', function () {
            btn.closest( '.hpcms-gallery-thumb' ).remove();
        } );
    }

    document.querySelectorAll( '.hpcms-remove-gallery-item' ).forEach( bindRemoveGalleryItem );

    // -------------------------------------------------------------------------
    // 4. Repeatable Location Rows
    // -------------------------------------------------------------------------
    const locWrap = document.getElementById( 'hpcms-locations-wrap' );
    if ( locWrap ) {
        document.querySelector( '.hpcms-add-location' )?.addEventListener( 'click', function () {
            const rows   = locWrap.querySelectorAll( '.hpcms-repeatable-row' );
            const newIdx = rows.length;
            const newId  = 'loc_' + Date.now();
            const row    = document.createElement( 'div' );
            row.className = 'hpcms-repeatable-row';
            row.innerHTML =
                '<input type="hidden" name="hpcms_general[locations][' + newIdx + '][id]" value="' + newId + '" class="hpcms-loc-id" />' +
                '<input type="text" name="hpcms_general[locations][' + newIdx + '][value]" value="" class="regular-text" placeholder="e.g. Remote" />' +
                '<button type="button" class="button hpcms-remove-row">Remove</button>';
            locWrap.appendChild( row );
            bindRemoveRow( row.querySelector( '.hpcms-remove-row' ) );
        } );

        function bindRemoveRow( btn ) {
            btn?.addEventListener( 'click', function () {
                btn.closest( '.hpcms-repeatable-row' ).remove();
                reindexRows( locWrap, 'hpcms_general[locations]' );
            } );
        }

        locWrap.querySelectorAll( '.hpcms-remove-row' ).forEach( bindRemoveRow );
    }

    // -------------------------------------------------------------------------
    // 5. Repeatable Highlighted Cards (v1.2.0+)
    // -------------------------------------------------------------------------
    const cardsWrap = document.getElementById( 'hpcms-cards-wrap' );
    if ( cardsWrap ) {
        document.querySelector( '.hpcms-add-card' )?.addEventListener( 'click', function () {
            const rows   = cardsWrap.querySelectorAll( '.hpcms-card-row' );
            const newIdx = rows.length;
            const newId  = 'card_' + Date.now();
            const row    = document.createElement( 'div' );
            row.className = 'hpcms-card-row';
            row.innerHTML =
                '<input type="hidden" name="hpcms_homepage[highlighted_cards][cards][' + newIdx + '][id]" value="' + newId + '" />' +
                '<p><label>Title</label><input type="text" name="hpcms_homepage[highlighted_cards][cards][' + newIdx + '][title]" placeholder="Card Title" class="regular-text" /></p>' +
                '<p><label>Subtitle</label><input type="text" name="hpcms_homepage[highlighted_cards][cards][' + newIdx + '][subtitle]" placeholder="Card Subtitle" class="regular-text" /></p>' +
                '<p><label>Icon</label><input type="text" name="hpcms_homepage[highlighted_cards][cards][' + newIdx + '][icon]" placeholder="Lucide name, SVG, or URL" class="regular-text" /></p>' +
                '<button type="button" class="button hpcms-remove-card">Remove</button>';
            cardsWrap.appendChild( row );
            row.querySelector( '.hpcms-remove-card' ).addEventListener( 'click', function () {
                row.remove();
            } );
        } );

        cardsWrap.querySelectorAll( '.hpcms-remove-card' ).forEach( function ( btn ) {
            btn.addEventListener( 'click', function () {
                btn.closest( '.hpcms-card-row' ).remove();
            } );
        } );
    }

    // -------------------------------------------------------------------------
    // 6. Accordion toggle with is-open class (v1.2.0+ Home Page sections)
    // -------------------------------------------------------------------------
    document.querySelectorAll( '.hpcms-accordion-toggle' ).forEach( function ( toggle ) {
        toggle.addEventListener( 'click', function () {
            const section = toggle.closest( '.hpcms-accordion-section' );
            const body    = toggle.nextElementSibling;
            const isOpen  = section.classList.contains( 'is-open' );

            if ( isOpen ) {
                section.classList.remove( 'is-open' );
                body.style.display = 'none';
            } else {
                section.classList.add( 'is-open' );
                body.style.display = 'block';
            }
        } );
    } );

    // -------------------------------------------------------------------------
    // 7. Repeatable Skill Tags (v1.2.0+)
    // -------------------------------------------------------------------------
    document.querySelectorAll( '.hpcms-add-tag' ).forEach( function ( btn ) {
        btn.addEventListener( 'click', function () {
            const wrapId = btn.dataset.wrap;
            const name   = btn.dataset.name;
            const wrap   = document.getElementById( wrapId );
            if ( ! wrap ) return;

            const row     = document.createElement( 'div' );
            row.className = 'hpcms-tag-row';
            row.innerHTML =
                '<input type="text" name="' + name + '" value="" class="regular-text" placeholder="e.g. React" />' +
                '<button type="button" class="button hpcms-remove-tag">Remove</button>';
            wrap.appendChild( row );

            row.querySelector( '.hpcms-remove-tag' ).addEventListener( 'click', function () {
                row.remove();
            } );
        } );
    } );

    document.querySelectorAll( '.hpcms-remove-tag' ).forEach( function ( btn ) {
        btn.addEventListener( 'click', function () {
            btn.closest( '.hpcms-tag-row' ).remove();
        } );
    } );

    // -------------------------------------------------------------------------
    // Helper: reindex array input names after a row is removed.
    // -------------------------------------------------------------------------
    function reindexRows( wrap, baseName ) {
        wrap.querySelectorAll( '.hpcms-repeatable-row' ).forEach( function ( row, i ) {
            row.querySelectorAll( 'input' ).forEach( function ( input ) {
                input.name = input.name.replace( /\[\d+\]/, '[' + i + ']' );
            } );
        } );
    }

} )();
