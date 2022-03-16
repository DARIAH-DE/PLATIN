/*
* DatafilterWidget.js
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
 * @class DatafilterWidget
 * DatafilterWidget Implementation
 * @author Kathrin Marquardt (Kathrin.Marquardt@sbb.spk-berlin.de)
 *
 * @param {WidgetWrapper} core wrapper for interaction to other widgets
 * @param {HTML object} div parent div to append the Datafilter widget div
 */
DatafilterWidget = function(core, div) {

    this.core = core;
    this.core.setWidget(this);

    var filterConf = new DatafilterConfig();
    this.options = filterConf.options;
    this.dataFilterOpt = filterConf.dataFilterOpt;
    this.labelLang = filterConf.labelLang;
    this.gui = new DatafilterGui(this, div, this.options);

    this.dataFilter = new Datafilter(this);
    this.datasets = [];
    this.atFirst = true;
}

DatafilterWidget.prototype = {

    initWidget: function () {

        var dataloaderWidget = this;
    },

    highlightChanged: function (objects) {
        if (!GeoTemConfig.highlightEvents) {
            return;
        }
    },

    selectionChanged: function (selection) {
        if (!GeoTemConfig.selectionEvents) {
            return;
        }
    },

    triggerHighlight: function (item) {
    },

    tableSelection: function () {
    },

    deselection: function () {
    },

    filtering: function () {

      if (this.atFirst)
      {
          var dscount = this.datasets.length;
          for (var i = 0; i < dscount; i++)
          {
              this.dataFilter.dsObjects.push(this.datasets[i].objects);
          }
          this.atFirst = false;
      }
      else
      {
          var dscount = this.datasets.length;
          for (var i = 0; i < dscount; i++)
          {
              this.datasets[i].objects = this.dataFilter.dsObjects[i];
          }
      }

        for (var i = 0; i < this.dataFilterOpt.length; i++)
        {
            var eleName = this.dataFilterOpt[i][0];
            if (!document.getElementById(eleName))
            {
                return;
            }
            this.dataFilter.filters[eleName] = document.getElementById(eleName).value;
        }
        this.dataFilter.pickFilterData();
    },

    inverseFiltering: function () {
    },

    triggerRefining: function () {
    },

    reset: function () {

        for (var i = 0; i < this.dataFilterOpt.length; i++)
        {
            var eleName = this.dataFilterOpt[i][0];
            var ele = document.getElementById(eleName);

            ele.selectedIndex = 0;
        }
        GeoTemConfig.datasets = this.datasets;
        this.filterdDatasets = this.datasets;
    }

}

