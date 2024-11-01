(function ($) {

    function TpmrUploader(controller) {
        this.state = controller.state();
        this.controller = controller;
    }

    TpmrUploader.prototype = {

        init: function () {

            var that = this;

            this.account_id = this.state.get('sectionid');
            this.$form = $('#tpmr_media_uploader');
            this.$selectFilesButton = $('#tpmr_upload_select');
            this.$browseFile = this.$form.find('input[type=file]');
            this.$uploadButton = this.$form.find('#tpmr_upload_submit');
            this.$cancelButton = this.$form.find('.tpmr-cancel_upload');

            this.$browseFile.change(function (e) {
                var $this = $(this);
                if ($this.val().length > 0) {


                    that.$form.find('.tpmr-post-file-select').css('display', 'inline-block');
                    that.$form.find('.upload-ui').hide();

                    var files = e.target.files[0];
                    var title = files.name.substring(0, files.name.lastIndexOf('.'));
                    that.$form.find('input#tpmr_title').val(title);


                    that.previewImage({
                        file: e.target.files[0],
                        width: 130,
                        height: 130,
                        callback: function (thumbURL) {
                            that.$form.find('.tpmr-file_preview img').attr('src', thumbURL);
                        }
                    });
                }
            });

            this.$selectFilesButton.click(function (e) {
                e.preventDefault();
                that.$browseFile.trigger('click');
                return false;
            });

            this.$uploadButton.click(function (e) {
                e.preventDefault();

                var $this = $(this);
                var $frm = $this.closest('#tpmr_media_uploader');

                if ($frm.find('#tpmr_files').val().length > 0) {

                    var data = {
                        files: $frm.find('#tpmr_files')[0].files,
                        title: $frm.find('#tpmr_title').val(),
                        desc: $frm.find('#tpmr_description').val()
                    };

                    that.upload(data);

                }
                return false;
            });

            this.$cancelButton.click(function (e) {
                that.$form.find('.tpmr-file_preview img').attr('src', '');
                that.$form.find('.tpmr-post-file-select').hide();
                that.$form.find('.upload-ui').show();
                that.$browseFile.prop('files', null).val('');
                e.preventDefault();
            });
        },
        previewImage: function (args) {

            var reader = new FileReader();
            reader.onloadend = function () {

                var tempImg = new Image();
                tempImg.src = reader.result;
                tempImg.onload = function () {

                    var MAX_WIDTH = args.width;
                    var MAX_HEIGHT = args.height;
                    var tempW = tempImg.width;
                    var tempH = tempImg.height;
                    if (tempW > tempH) {
                        if (tempW > MAX_WIDTH) {
                            tempH *= MAX_WIDTH / tempW;
                            tempW = MAX_WIDTH;
                        }
                    } else {
                        if (tempH > MAX_HEIGHT) {
                            tempW *= MAX_HEIGHT / tempH;
                            tempH = MAX_HEIGHT;
                        }
                    }

                    var canvas = document.createElement('canvas');
                    canvas.width = tempW;
                    canvas.height = tempH;
                    var ctx = canvas.getContext("2d");
                    ctx.drawImage(this, 0, 0, tempW, tempH);
                    var thumbURL = canvas.toDataURL("image/jpeg");
                    if (typeof args.callback == 'function') {
                        args.callback(thumbURL);
                    }
                }

            }

            reader.readAsDataURL(args.file);
        },
        upload: function (data) {

            var that = this,
                    attributes = {
                        date: new Date(),
                        filename: data.title,
                        account_id: this.account_id,
                        loaded: 0,
                        menuOrder: 0,
                        percent: 0,
                        type: 'image',
                        subtype: 'tpmr',
                        remotedata: {id: ''},
                        uploading: true,
                        rerenderOnModelChange: false
                    };

            var newattach = new wp.media.model.Attachment(attributes);

            this.state.get('library').props.set('useronly', true);
            this.state.get('library').props.set('setId', 0);
            this.state.get('library').add(newattach);
            this.state.uploading(newattach);

            var formdata = new FormData();
            formdata.append('token', '');
            formdata.append('action', 'tpmr_upload_attachments');
            formdata.append('title', data.title);
            formdata.append('desc', data.desc);
            formdata.append('files', data.files[0]);

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: formdata,
                contentType: false,
                processData: false,
                success: function (res) {

                    newattach.set('percent', 100);

                    if (res.success) {

                        attributes = _.defaults(res.data, newattach);
                        newattach.set(attributes);

                        //Need to change uploading attribute at the end to force rerendering
                        newattach.set('uploading', false);
                        that.state.get('library').trigger('reset');
                        that.state.get('selection').trigger('selection:single');
                        that.state.get('library').trigger('start');
                    }
                },
                error: function (e) {

                }
            });

        }
    }

    $(function () {

        var beforeTpmrUploadInline = wp.media.view.TpmrUploadInline;

        wp.media.view.TpmrUploadInline = beforeTpmrUploadInline.extend({

            ready: function () {
                var state = this.controller.state(),
                        service = this.controller.state().get('service'),
                        tpmrUploader;

                beforeTpmrUploadInline.prototype.ready.apply(this, arguments);
                if (service === 'tpmr') {
                    tpmrUploader = new TpmrUploader(this.controller);
                    tpmrUploader.init();
                }
                return this;
            }
        });

        /**
         * Current feature image selection
         */
        var FeaturedImage = wp.media.controller.FeaturedImage;

        wp.media.controller.FeaturedImage = FeaturedImage.extend({
            updateSelection: function () {

                var featureImageAccount = wp.media.view.settings.post.featuredImageAccountId || 0,
                        stateAccountId = this.frame.state().get('sectionid') || 0;

                if (featureImageAccount === stateAccountId) {
                    FeaturedImage.prototype.updateSelection.apply(this, arguments);
                }
            }
        });

        var oldFeaturedImageSet = wp.media.featuredImage.set;

        wp.media.featuredImage.set = function () {
            //Make sure on legacy featuredImage set, image account is back empty
            wp.media.view.settings.post.featuredImageAccountId = 0;
            oldFeaturedImageSet.apply(this, arguments);
        };

        var oldFeaturedImageFrame = wp.media.featuredImage.frame;
        wp.media.featuredImage.frame = function () {
            var that = this,
                    options;

            if (this._frame) {
                return this._frame;
            }

            this._frame = oldFeaturedImageFrame.apply(this, arguments);

            options = this._frame.options;

            _.each(wp.media.view.settings.tpmr_accounts, function (account) {

                var featureImageAccount = wp.media.view.settings.post.featuredImageAccountId || 0;

                if (account.featuredSelectable !== true ||
                        account.type !== 'tpmr'
                        ) {
                    return;
                }

                if (featureImageAccount === account.id) {
                    this._frame.setState('tpmr-' + account.id);
                }
            }, this);

            return this._frame;
        };

        wp.media.featuredImage.tpmrFeaturedSelect = function () {

            var selection = this.get('selection').single();

            if (!wp.media.view.settings.post.featuredImageId) {
                return;
            }

            wp.media.featuredImage.tpmrSetImage(selection, this.get('sectionid'));
        };

        wp.media.featuredImage.tpmrSetImage = function (selection, accountid) {

            var settings = wp.media.view.settings;

            settings.post.featuredImageId = selection.attributes.id;
            settings.post.featuredImageAccountId = accountid;

            wp.media.post('tpmr_featured_html', {
                post_id: settings.post.id,
                attachment: selection.attributes,
                image_size: 'medium',
                security: settings.post.nonce
            }).done(function (html) {
                $('.inside', '#postimagediv').html(html);
            });

        };

        var origMediaFrameSelect = wp.media.view.MediaFrame.Select;

        wp.media.view.MediaFrame.Select = origMediaFrameSelect.extend({
            createStates: function () {
                var that = this,
                        state = this.state(),
                        options = this.options || {};

                origMediaFrameSelect.prototype.createStates.apply(this, arguments);

                _.each(wp.media.view.settings.tpmr_accounts, function (account) {

                    var serviceSettings = wp.media.view.settings.tpmr_services[account.type] || [], tpmrState,
                            featureImageAccount = wp.media.view.settings.post.featuredImageAccountId || 0;

                    if (account.featuredSelectable !== true || account.type !== 'tpmr') {
                        return;
                    }

                    if (options.state === 'featured-image') {
                        tpmrState = new wp.media.controller.FeaturedImage({
                            id: 'tpmr-' + account.id,
                            isFeaturedImages: true,
                            sectionid: account.id,
                            title: account.featuredtitle,
                            service: account.type,
                            priority: 300,
                            multiple: false,
                            remote: true,
                            date: false,
                            uploadTemplate: serviceSettings.uploadTemplate || '',
                            filterable: account.filterable || false,
                            library: wp.media.remotequery(_.defaults({
                                type: 'image',
                                account_id: account.id,
                                remotefilters: account.filters || [],
                                uioptions: account.uioptions || [],
                                orderby: 'menuOrder',
                                order: 'ASC'
                            }, options.library)),
                            state: 'tpmr-' + account.id,
                            content: 'upload',
                            menu: 'default',
                            AttachmentView: wp.media.view.Attachment.TpmrLibrary
                        });

                    } else {
                        tpmrState = new wp.media.controller.TpmrLibrary({
                            id: 'tpmr-' + account.id,
                            isFeaturedImages: false,
                            sectionid: account.id,
                            title: account.featuredtitle,
                            toolbar: 'select',
                            service: account.type,
                            priority: 300,
                            multiple: false,
                            remote: true,
                            date: false,
                            uploadTemplate: serviceSettings.uploadTemplate || '',
                            filterable: account.filterable || false,
                            library: wp.media.remotequery(_.defaults({
                                type: 'image',
                                account_id: account.id,
                                remotefilters: account.filters || [],
                                uioptions: account.uioptions || [],
                                orderby: 'menuOrder',
                                order: 'ASC'
                            }, this.options.library)),
                            state: 'tpmr-' + account.id,
                            // content:    'upload',
                            // menu:       'default',
                            AttachmentView: wp.media.view.Attachment.TpmrLibrary
                        });
                    }
                    this.states.add([tpmrState]);

                    tpmrState.on('select', this.tpmrFeaturedSelect);
                }, this);

            },
            createSelectToolbar: function (toolbar, options) {
                if (this.options.button && this.options.button.items) {
                    delete this.options.button.items;
                }
                origMediaFrameSelect.prototype.createSelectToolbar.apply(this, arguments);
            },
            tpmrFeaturedSelect: function () {
                var settings = wp.media.view.settings, xhr, attachment;
//                console.log(this.get('selection').first());
                if (this.attributes.isFeaturedImages === true) {
                    return wp.media.featuredImage.tpmrFeaturedSelect.apply(this);
                }

            }
        });

    });



    /**
     * Overide post image settings detail
     */
//    wp.media.model.PostImage.prototype.initialize = function (attributes) {
//
//        var that = this;
//
//        if (isNaN(attributes.attachment_id)) {
//
//            var Attachment = wp.media.model.Attachment;
//
//            that.attachment = false;
//
//            that.attachment = Attachment.get(attributes.attachment_id);
//            console.log(that.attachment);
//            $.ajax({
//                type: 'POST',
//                url: ajaxurl,
//                data: {action: 'tpmr_to_attachment', attachment_id: 'a0EGgGQ'},
//                success: function (res) {
//
//                    if (res.success) {
//                        that.attachment.set(res.data);
//                        console.log(that.attachment);
//                    }
//                    
//                    console.log(that.attachment.get('url'));
//                    if (that.attachment.get('url')) {
//                        that.dfd = jQuery.Deferred();
//                        that.dfd.resolve();
//                    } else {
//                        that.dfd = that.attachment.fetch();
//                    }
//                    that.bindAttachmentListeners();
//                    
//
//                    // keep url in sync with changes to the type of link
//                    that.on('change:link', that.updateLinkUrl, that);
//                    that.on('change:size', that.updateSize, that);
//
//                    that.setLinkTypeFromUrl();
//                    that.setAspectRatio();
//
//                    that.set('originalUrl', attributes.url);
//                }
//            });
//
//        }
//    }

}(jQuery));


