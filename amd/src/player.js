// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * player.js
 *
 * @package   mod_videotrackermax
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
