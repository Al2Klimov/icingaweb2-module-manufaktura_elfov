// SPDX-License-Identifier: AGPL-3.0-or-later

window.onload = function () {
    var randMax = Math.pow(2, 16)

    setupRandomLinks();

    function setupRandomLinks() {
        var randomLinks = document.querySelectorAll('a.random-link');

        if (!randomLinks.length) {
            setTimeout(setupRandomLinks, 500);
            return;
        }

        randomLinks.forEach(function (a) {
            var div = a.closest('div.content')

            if (div === null) {
                return;
            }

            var tbody = div.querySelector('table.common-table tbody')

            if (tbody === null) {
                return;
            }

            var tr = tbody.querySelectorAll('tr.random-target')

            if (tr.length < 1) {
                return;
            }

            a.onclick = function () {
                var rand = new Uint16Array(1);

                window.crypto.getRandomValues(rand);
                tr[Math.floor(tr.length * (rand[0] / randMax))].click();
            }
        });
    }
};
