/*
* StorytellingWidget.js
*
* Copyright (c) 2013, Sebastian Kruse. All rights reserved.
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
 * @class StorytellingWidget
 * StorytellingWidget Implementation
 * @author Sebastian Kruse (skruse@mpiwg-berlin.mpg.de)
 *
 * @param {WidgetWrapper} core wrapper for interaction to other widgets
 * @param {HTML object} div parent div to append the Storytelling widget div
 * @param {JSON} options user specified configuration that overwrites options in StorytellingConfig.js
 */
StorytellingWidget = function(core, div, options) {

	this.datasets;
	this.core = core;
	this.core.setWidget(this);
	this.currentStatus = new Object();

	this.options = (new StorytellingConfig(options)).options;
	this.gui = new StorytellingGui(this, div, this.options);

	this.datasetLink;

	Publisher.Subscribe('mapChanged', this, function(mapName) {
		this.client.currentStatus["mapChanged"] = mapName;
		this.client.createLink();
	});

	var currentStatus = $.url().param("currentStatus");
	if (typeof currentStatus !== "undefined"){
		this.currentStatus = $.deparam(currentStatus);
		$.each(this.currentStatus,function(action,data){
			Publisher.Publish(action, data, this);
		});
	}
}

StorytellingWidget.prototype = {

	initWidget : function(data) {
		var storytellingWidget = this;
		var gui = storytellingWidget.gui;

		storytellingWidget.datasets = data;

		$(gui.storytellingContainer).empty();

		var magneticLinkParam = "";
		var datasetIndex = 0;
		var linkCount = 1;
		$(storytellingWidget.datasets).each(function(){
			var dataset = this;

			if (magneticLinkParam.length > 0) {
				magneticLinkParam += "&";
            }

            // Get color of dataset and create <p> tag.
            var color = 'rgb(' + dataset.color.r0 + ',' + dataset.color.g0 + ',' + dataset.color.b0 + ')';
            // Style color hack. Don't do it! :-D --fu
			var paragraph = $("<p style='background-color:" + color + ";margin-bottom:5px;'></p>");
			paragraph.append(dataset.label);
			if (typeof dataset.url !== "undefined"){

				// TODO: makes only sense for KML or CSV URLs, so "type" of
				// URL should be preserved (in dataset).
				// startsWith and endsWith defined in SIMILE Ajax (string.js)
				var type = "csv";
				if (typeof dataset.type !== "undefined") {
					type = dataset.type;
                } else {
					if (dataset.url.toLowerCase().endsWith("kml")) {
						type = "kml";
                    }
				}

				magneticLinkParam += type+linkCount + "=";
				linkCount++;
				magneticLinkParam += dataset.url;

                var tableLinkDiv = document.createElement('a');
				tableLinkDiv.title = 'Open/download dataset ' + dataset.label + ' directly from source location. Datasets from DARIAH-DE OwnStorage must be public or you need to be owner of the dataset.';
				tableLinkDiv.href = dataset.url;
				tableLinkDiv.target = '_';
				tableLinkDiv.setAttribute('class', 'externalLink');
				paragraph.append(tableLinkDiv);
                // Provide link to Datasheet Editor if stored in DARIAH-DE OwnStorage.
                if (dataset.url.includes(GeoTemConfig.dariahOwnStorageURL)) {
                    var datasheetLinkDiv = document.createElement('a');
					$(datasheetLinkDiv).append("[open datasheet]");
                    datasheetLinkDiv.title = 'Open datasheet ' + dataset.label + ' from DARIAH-DE OwnStorage in Datasheet Editor. The dataset must be public or you need to be owner of the dataset.';
                    datasheetLinkDiv.href = GeoTemConfig.datasheetEditorURL + '?id=' + dataset.label;
                    datasheetLinkDiv.target = '_';
                    paragraph.append(' ');
                    paragraph.append(datasheetLinkDiv);
                }
			} else {
				if (storytellingWidget.options.dariahStorage){
					var uploadToDARIAH = document.createElement('a');
					$(uploadToDARIAH).append(" [upload to DARIAH-DE Storage]");
					uploadToDARIAH.title = "Only CSV documents can be uploaded to the DARIAH-DE Storage, so you can edit them using the Datasheet Editor. If the dataset is not already in CSV format, it will be converted automatically and then be uploaded. The filename of your uploaded file will be lost and you have to login first! We apologise for the inconvenience!";
					uploadToDARIAH.href = dataset.url;
					var localDatasetIndex = new Number(datasetIndex);
                    $(uploadToDARIAH).click(function(){
						var csv = GeoTemConfig.createCSVfromDataset(localDatasetIndex);
                        GeoTemConfig.storeToDariahStorage(csv, function(location, id) {
                            // Set (global) DSID.
                            dsid = id;
                            // Add URL to dataset.
                            storytellingWidget.datasets[localDatasetIndex].url = location;
                            storytellingWidget.datasets[localDatasetIndex].type = "csv";
                            storytellingWidget.datasets[localDatasetIndex].label = dsid;
                            // Refresh list.
                            storytellingWidget.initWidget(storytellingWidget.datasets);
                        });
						//discard link click-event
						return(false);
					});
					paragraph.append(uploadToDARIAH);
				}
				// TODO: if layout is more usable, both options could be used ("else" removed)
				else if (storytellingWidget.options.localStorage) {
					var saveToLocalStorage = document.createElement('a');
					$(saveToLocalStorage).append(" [save to local storage]");
					saveToLocalStorage.title = "";
					saveToLocalStorage.href = dataset.url;
					var localDatasetIndex = new Number(datasetIndex);
					$(saveToLocalStorage).click(function(){
						var csv = GeoTemConfig.createCSVfromDataset(localDatasetIndex);
						var storageName = "GeoBrowser_dataset_" + GeoTemConfig.datasets[localDatasetIndex].label;
						$.remember({
							name:storageName,
							value:csv
						});
						//add URL to dataset
					    storytellingWidget.datasets[localDatasetIndex].url = storageName;
					    storytellingWidget.datasets[localDatasetIndex].type = "local";
					    //refresh list
					    storytellingWidget.initWidget(storytellingWidget.datasets);
						//discard link click-event
						return(false);
					});
					paragraph.append(saveToLocalStorage);
				}
			}

			$(gui.storytellingContainer).append(paragraph);
			datasetIndex++;
		});

		this.datasetLink = magneticLinkParam;
		this.createLink();
	},

	createLink : function() {
        // Remove <p class="magneticLink"/> first.
        $(this.gui.storytellingContainer).find('.magneticLink').remove();

        // Create new magnetic link reference.
		var magneticLink = document.createElement('a');
		$(magneticLink).append("Magnetic link");
		magneticLink.title = "Use this link to reload or share currently loaded view of online datasets.";
		magneticLink.href = "?" + this.datasetLink;
		var currentStatusParam = $.param(this.currentStatus);
		if (currentStatusParam.length > 0) {
			magneticLink.href += "&currentStatus="+currentStatusParam;
        }
		magneticLink.target = '_';

        // Add paragraph containing newly created magnetic link.
        var paragraph = document.createElement('p');
        paragraph.setAttribute('class', 'magneticLink');
        paragraph.append(magneticLink);
        paragraph.append(' (online datasets only)');
		$(this.gui.storytellingContainer).prepend(paragraph);
	},

	highlightChanged : function(objects) {
	},

	selectionChanged : function(selection) {
	},
};
