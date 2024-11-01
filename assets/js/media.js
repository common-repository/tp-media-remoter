jQuery(function ($) {

    var oldSendAttachment = wp.media.editor.send.attachment,
            sendRemoteAttachment = function (props, attachment) {

                if (typeof attachment.isRemote === 'undefined' || attachment.isRemote === false) {
                    return oldSendAttachment(props, attachment);
                }

                attachment.image_size = props.size;
                attachment.image_alt = props.hasOwnProperty('alt') ? props.alt : '';
                attachment.align = props.align;

                if (attachment.hasOwnProperty('toEditor')) {
                    return attachment.toEditor;
                }

                return wp.media.post('tpmr_attachment_to_editor', {
                    nonce: tpmr_var.nonce,
                    attachment: attachment,
                    post_id: wp.media.view.settings.post.id
                });

            };

    wp.media.editor.send.attachment = sendRemoteAttachment;

    /**
     * wp.media.view.TpmrUploadInline
     */
    wp.media.view.TpmrUploadInline = wp.media.View.extend({
        tagName: 'div',
        className: 'tpmr-uploader uploader-inline',
        template: wp.media.template('attachment-upload'),
        // bind view events
        events: {
            'input': 'refresh',
            'keyup': 'refresh',
            'change': 'refresh'
        },
        initialize: function () {

            _.defaults(this.options, {
                message: '',
                status: true,
                canClose: false
            });

            var state = this.controller.state(),
                    template = state.get('uploadTemplate');
            if (template) {
                this.template = wp.media.template(template);
            }

            if (this.options.status) {
                this.views.set('.upload-inline-status', new wp.media.view.UploaderStatus({
                    controller: this.controller
                }));
            }

        },
        render: function () {
            wp.media.View.prototype.render.apply(this, arguments);
            this.refresh();
            return this;
        },
        refresh: function (event) {},
        hide: function () {
            this.$el.addClass('hidden');
        }
    });

    /**
     * RemoteAttachments
     */
    wp.media.remotequery = function (props) {
        return new wp.media.model.RemoteAttachments(null, {
            props: _.extend(_.defaults(props || {}, {orderby: 'date'}), {query: true})
        });
    };

    wp.media.model.RemoteAttachments = wp.media.model.Attachments.extend({
        initialize: function () {

            wp.media.model.Attachments.prototype.initialize.apply(this, arguments);
        },
        _requery: function () {
            if (this.props.get('query'))
                this.mirror(wp.media.model.TpmrQuery.get(this.props.toJSON()));
        }
    });

    wp.media.model.TpmrQuery = wp.media.model.Query.extend({
        initialize: function () {
            wp.media.model.Query.prototype.initialize.apply(this, arguments);
        },
        sync: function (method, model, options) {
            var fallback;

            // Overload the read method so Attachment.fetch() functions correctly.
            if ('read' === method) {

                options = options || {};

                options.context = this;

                options.data = _.extend(options.data || {}, {
                    action: 'tpmr_remote_attachments',
                    post_id: wp.media.model.settings.post.id,
                    security: tpmr_var.nonce
                });

                // Clone the args so manipulation is non-destructive.
                args = _.clone(this.args);

                // Determine which page to query.
                if (-1 !== args.posts_per_page)
                    args.paged = Math.floor(this.length / args.posts_per_page) + 1;

                options.data.query = args;
                return wp.media.ajax(options);

                // Otherwise, fall back to Backbone.sync()
            } else {
                fallback = wp.media.model.Attachments.prototype.sync ? wp.media.model.Attachments.prototype : Backbone;
                return fallback.sync.apply(this, arguments);
            }
        }
    }, {
        // Caches query objects so queries can be easily reused.
        get: (function () {
            var queries = [];

            return function (props, options) {
                var args = {},
                        orderby = wp.media.model.TpmrQuery.orderby,
                        defaults = wp.media.model.TpmrQuery.defaultProps,
                        query;

                // Remove the `query` property. This isn't linked to a query,
                // this *is* the query.
                delete props.query;

                // Remove the `remotefilters` property. 
                delete props.remotefilters;

                // Remove the `uioptions` property. 
                delete props.uioptions;

                // Fill default args.
                _.defaults(props, defaults);

                // Normalize the order.
                props.order = props.order.toUpperCase();

                if ('DESC' !== props.order && 'ASC' !== props.order)
                    props.order = defaults.order.toUpperCase();

                // Ensure we have a valid orderby value.
                if (!_.contains(orderby.allowed, props.orderby))
                    props.orderby = defaults.orderby;

                // Generate the query `args` object.
                // Correct any differing property names.
                _.each(props, function (value, prop) {
                    if (_.isNull(value))
                        return;

                    args[ wp.media.model.TpmrQuery.propmap[ prop ] || prop ] = value;
                });

                // Fill any other default query args.
                _.defaults(args, wp.media.model.TpmrQuery.defaultArgs);

                // `props.orderby` does not always map directly to `args.orderby`.
                // Substitute exceptions specified in orderby.keymap.
                args.orderby = orderby.valuemap[ props.orderby ] || props.orderby;

                // Search the query cache for matches.
                query = _.find(queries, function (query) {
                    return _.isEqual(query.args, args);
                });

                // Otherwise, create a new query and add it to the cache.
                if (!query) {
                    query = new wp.media.model.TpmrQuery([], _.extend(options || {}, {
                        props: props,
                        args: args
                    }));
                    queries.push(query);
                }
                return query;
            };
        }())
    });

    /**
     * wp.media.view.AttachmentFilters.RemoteCustom
     *
     */
    wp.media.view.AttachmentFilters.RemoteCustom = wp.media.view.AttachmentFilters.extend({
        className: 'attachment-filters',
        createFilters: function () {
            var filters = {},
                    remotefilters = this.model.get('remotefilters');

            _.each(remotefilters, function (remotefilter) {
                var filter = {
                    text: remotefilter.text || 'Undefined',
                    props: remotefilter.props || {uploadedTo: null, orderby: 'date', order: 'DESC'},
                    priority: remotefilter.priority || 10
                }
                if (remotefilter.slug) {
                    filters[remotefilter.slug] = (filter);
                }

            });

            this.filters = filters;
        }
    });

    /**
     * wp.media.controller.TpmrLibrary
     */
    wp.media.controller.TpmrLibrary = wp.media.controller.Library.extend({
        defaults: {
            id: 'tpmr',
            multiple: 'add', // false, 'add', 'reset'
            describe: false,
            toolbar: 'select',
            sidebar: 'settings',
            content: 'upload',
            router: 'browse',
            menu: 'default',
            date: false,
            remote: true,
            searchable: true,
            filterable: false,
            sortable: true,
            autoSelect: true,

            // Allow local edit of attachment details like title, caption, alt text and description
            allowLocalEdits: true,

            // Uses a user setting to override the content mode.
            contentUserSetting: true,

            // Sync the selection from the last state when 'multiple' matches.
            syncSelection: true
        }
    });

    /**
     * New wp.media.view.MediaFrame.Post
     */
    var oldMediaFrame = wp.media.view.MediaFrame.Post;

    wp.media.view.MediaFrame.Post = oldMediaFrame.extend({
        initialize: function () {
            oldMediaFrame.prototype.initialize.apply(this, arguments);
        },

        createStates: function () {
            oldMediaFrame.prototype.createStates.apply(this, arguments);

            var options = this.options,
                    that = this;

            _.each(wp.media.view.settings.tpmr_accounts, function (account) {
                var serviceSettings = wp.media.view.settings.tpmr_services[account.type] || [];
                that.states.add([
                    new wp.media.controller.TpmrLibrary({
                        id: 'tpmr-' + account.id,
                        sectionid: account.id,
                        title: account.title,
                        service: account.type,
                        priority: 30,
                        toolbar: 'main-remote',
                        uploadTemplate: serviceSettings.tpl_upload || '',
                        filterable: account.filterable || 'uploaded',
                        library: wp.media.remotequery(_.defaults({
                            isRemote: true,
                            account_id: account.id,
                            remotefilters: account.filters || [],
                            uioptions: account.uioptions || [],
                            orderby: 'menuOrder',
                            order: 'ASC'
                        }, options.library)),
                        state: 'tpmr-' + account.id,
                        editable: true,
                        displaySettings: true,
                        displayUserSettings: true,
                        menu: 'default',
                        AttachmentView: wp.media.view.Attachment.TpmrLibrary
                    })
                ]);
            }, this);
        },
        bindHandlers: function () {
            oldMediaFrame.prototype.bindHandlers.apply(this, arguments);

            this.on('toolbar:create:main-remote', this.createToolbar, this);
            this.on('toolbar:render:main-remote', this.mainInsertToolbar, this);
        },
        uploadContent: function () {
            var sectionid = this.state().get('sectionid');
            if (sectionid) {
                this.$el.removeClass('hide-toolbar');

                this.content.set(new wp.media.view.TpmrUploadInline({
                    controller: this,
                    model: this.state().props
                }));

            } else {
                wp.media.view.MediaFrame.Select.prototype.uploadContent.apply(this, arguments);
            }
        }
    });

    /**
     * Active current thumbnail
     */
    wp.media.view.TpmrAttachmentsBrowser = wp.media.view.AttachmentsBrowser.extend({
        deleteAttachment: function (e) {

            this.$el.find('li.selected').remove();

        },
        createSingle: function () {

            var sidebar = this.sidebar,
                    single = this.options.selection.single(),
                    type = single.get('type'),
                    isRemote = single.get('isRemote') || false;

            if (isRemote !== true) {
                return wp.media.view.AttachmentsBrowser.prototype.createSingle.apply(this, arguments);
            }

            //Set type from remote type to display same attachment display settings than native supported type
            wp.media.view.AttachmentsBrowser.prototype.createSingle.apply(this, arguments);

            // Show the sidebar on mobile
            if (this.model.id === 'tpmr-' + this.model.get('sectionid')) {
                sidebar.$el.addClass('visible');
                sidebar.$el.find('.delete-attachment').removeClass('delete-attachment').addClass('tpmr-delete-attachment');
                var del = _.bind(this.deleteAttachment, this);
                sidebar.$el.on('click', '.tpmr-delete-attachment', function (e) {
                    if (window.confirm(wp.media.view.l10n.warnDelete)) {
                        del.call(this);
                    }
                    e.preventDefault();
                });
            }
        },
        createToolbar: function () {

            wp.media.view.AttachmentsBrowser.prototype.createToolbar.apply(this, arguments);

            if ('custom' === this.options.filters) {
                this.toolbar.set('filters', new wp.media.view.AttachmentFilters.RemoteCustom({
                    controller: this.controller,
                    model: this.collection.props,
                    priority: -80
                }).render());
            }

            //Add class to attachments browser to distinguish remote browser and allow targetted styling 
            this.$el.addClass('remote-attachments-browser');

        }

    });

    var oldBrowseContent = wp.media.view.MediaFrame.Select.prototype.browseContent;

    wp.media.view.MediaFrame.Select.prototype.browseContent = function (contentRegion) {
        var state = this.state(),
                isRemoteLibrary = state.get('remote');
        if (isRemoteLibrary === true) {

            this.$el.removeClass('hide-toolbar');

            // Browse our library of attachments.
            contentRegion.view = new wp.media.view.TpmrAttachmentsBrowser({
                controller: this,
                collection: state.get('library'),
                selection: state.get('selection'),
                model: state,
                sortable: state.get('sortable'),
                search: state.get('searchable'),
                filters: state.get('filterable'),
                date: state.get('date'),
                display: state.has('display') ? state.get('display') : state.get('displaySettings'),
                dragInfo: state.get('dragInfo'),

                idealColumnWidth: state.get('idealColumnWidth'),
                suggestedWidth: state.get('suggestedWidth'),
                suggestedHeight: state.get('suggestedHeight'),

                AttachmentView: state.get('AttachmentView')
            });
        } else {
            oldBrowseContent.apply(this, arguments);
        }
    }

    /**
     * wp.media.view.Attachment.TpmrLibrary
     */
    wp.media.view.Attachment.TpmrLibrary = wp.media.view.Attachment.Library.extend({
        template: wp.media.template('tpmr_attachment_remoter'),
        toggleSelection: function ( ) {
            wp.media.view.Attachment.Library.prototype.toggleSelection.apply(this, arguments);
        },
        onTest: function () {
            this.model.destroy();
        },
        initialize: function () {

           // var onTest = _.bind(this.onTest, this);
//
//            this.$el.on('click', function () {
//                onTest.call(this);
//            });

            this.options = _.defaults(this.options, {
                rerenderOnModelChange: false
            });

            this.on('ready', this.initialFocus);

            // Call 'initialize' directly on the parent class.
            wp.media.view.Attachment.prototype.initialize.apply(this, arguments);

        }
    });



    /**
     * wp.media.view.Attachment.Selection
     */
    wp.media.view.Attachment.TpmrSelection = wp.media.view.Attachment.Selection.extend({
        template: wp.media.template('tpmr_attachment_remoter')
    });

    /**
     * wp.media.view.Attachments.Selection
     * 
     * Use new TpmrSelection view by default
     */
    var oldAttachmentsSelection = wp.media.view.Attachments.Selection;

    wp.media.view.Attachments.Selection = oldAttachmentsSelection.extend({
        initialize: function () {
            _.defaults(this.options, {
                // The single `Attachment` view to be used in the `Attachments` view.
                AttachmentView: wp.media.view.Attachment.TpmrSelection
            });

            return oldAttachmentsSelection.prototype.initialize.apply(this, arguments);
        }
    });

});
