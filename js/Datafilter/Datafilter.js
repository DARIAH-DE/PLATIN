/*
* Datafilter.js
*
* Copyright (c) 2022, Kathrin Marquardt. All rights reserved.
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU Lesser General Public
* License as published by the Free Software Foundation; either
* version 3 of the License, or (at your option) any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
* Lesser General Public License for more details.
*
* You should have received a copy of the GNU Lesser General Public
* License along with this library; if not, write to the Free Software
* Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
* MA 02110-1301  USA
*/


/**
 * @class Datafilter
 * Implementation for a Datafilter UI
 * @author Kathrin Marquardt (Kathrin.Marquardt@sbb.spk-berlin.de)
 *
 * @param {HTML object} parent div to append the Datafilter
 */
function Datafilter(parent) {

    this.datafilter = this;

    this.parent = parent;
    this.filters = [];
    this.filteredDatasets = [];
    this.dsObjects= [];

    this.initialize();
}

Datafilter.prototype = {

    show: function () {
        this.parent.gui.htmlWrap.display = "block";
    },

    hide: function () {
        this.parent.gui.htmlWrap.style.display = "none";
    },

    initialize: function () {

        var optCount = this.parent.dataFilterOpt.length;
        var dsCount = GeoTemConfig.datasets.length;

        for (var i = 0; i < optCount; i++)
        {
            this.filters[this.parent.dataFilterOpt[i][0]] = '';
        }

    },

    pickFilterData: function () {

        var optCount = this.parent.dataFilterOpt.length;
        var datasetsCount = this.parent.datasets.length;
        var aktFilter = [];
        var aktFields = [];
        var zaehler = 0;
        var emptySet = true;

        for (var i = 0; i < optCount; i++) {

            if (this.filters[this.parent.dataFilterOpt[i][0]] != -1)
            {
                aktFilter[zaehler] = this.filters[this.parent.dataFilterOpt[i][0]];
                aktFields[zaehler] = this.parent.dataFilterOpt[i][2];
                zaehler++;
            }
        }

        // At changing datasets will be disabled animation button
        var timePlotMarker = document.getElementsByClassName("plotHandleBox");
        var plotButton = null;
        if (timePlotMarker != null && timePlotMarker.length > 0)
        {
            var timePlotEle = document.getElementById("plotContainerDiv");
            var toolbarEle = timePlotEle.getElementsByClassName("ddbToolbar")[0];
            plotButton = toolbarEle.getElementsByClassName("pauseEnabled")[0];
        }

        if (zaehler == 0)
        {
            for (var i = 0; i < datasetsCount; i++)
            {
                this.parent.datasets[i].objects = this.dsObjects[i];
                GeoTemConfig.datasets[i].objects = this.dsObjects[i];
                emptySet = false;
            }
        }
        else {
            for (var j = 0; j < datasetsCount; j++) {

                var dsCount = this.parent.datasets[j].objects.length;
                var dsFilter = [];
                for (var i = 0; i < dsCount; i++) {
                    for (var k = 0; k < zaehler; k++) {

                        if (this.parent.datasets[j].objects[i].tableContent[aktFields[k]] != aktFilter[k]) {
                            break;
                        }
                        if (k == zaehler - 1) {
                            dsFilter.push(this.parent.datasets[j].objects[i]);
                        }
                    }
                }
                if (dsFilter.length > 0)
                {
                    emptySet = false;
                }
                GeoTemConfig.datasets[j].objects = dsFilter;
            }
        }

        if (emptySet)
        {
            alert('Für den aktuellen Filter gibt es keine Treffer. \r\n There are no hits for the current filter.');

            // When changing dataset with animated timeplot you have to enable stop-animation-button
            if (plotButton != null)
            {
                var plotBtn = toolbarEle.getElementsByClassName("pauseDisabled")[0];
                plotBtn.classList.remove("pauseDisabled");
                plotBtn.classList.add("pauseEnabled");
            }
            return;
        }
        Publisher.Publish('filterData', GeoTemConfig.datasets, null);

        // When changing dataset with animated timeplot you have to enable stop-animation-button
        if (plotButton != null)
        {
            var plotBtn = toolbarEle.getElementsByClassName("pauseDisabled")[0];
            plotBtn.classList.remove("pauseDisabled");
            plotBtn.classList.add("pauseEnabled");
        }

    }

}