// This file is part of Moodle - http://moodle.org/

define(['core/notification', 'local_video_bridge/progress'], function(Notification, Progress) {
    const create = (root, config) => {
        return new Promise((resolve, reject) => {
            if (!config.adaptermodule) {
                reject(new Error('Missing Video Bridge adapter module.'));
                return;
            }
            require([config.adaptermodule], (provider) => {
                if (!provider || typeof provider.create !== 'function') {
                    reject(new Error('Invalid Video Bridge adapter module.'));
                    return;
                }
                Promise.resolve(provider.create(root, config))
                    .then((adapter) => resolve(Progress.attach(adapter, root, config)))
                    .catch(reject);
            }, reject);
        });
    };

    const init = () => {
        document.querySelectorAll('[data-region="videotrackermax"]').forEach((activity) => {
            const root = activity.querySelector('[data-region="player"]');
            try {
                const config = JSON.parse(activity.dataset.config || '{}');
                create(root, config).catch(Notification.exception);
            } catch (error) {
                Notification.exception(error);
            }
        });
    };

    return {init: init, create: create};
});
