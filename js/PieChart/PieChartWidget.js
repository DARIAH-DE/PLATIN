/*
* PieChartWidget.js
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
 * @class PieChartWidget
 * PieChartWidget Implementation
 * @author Sebastian Kruse (skruse@mpiwg-berlin.mpg.de)
 *
 * @param {WidgetWrapper} core wrapper for interaction to other widgets
 * @param {HTML object} div parent div to append the PieChart widget div
 * @param {JSON} options user specified configuration that overwrites options in PieChartConfig.js
 */
function PieChartWidget(core, div, options) {

	this.datasets;
	this.core = core;
	this.core.setWidget(this);

	this.options = (new PieChartConfig(options)).options;
	this.gui = new PieChartGui(this, div, this.options);
	
	this.pieCharts = [];
}

PieChartWidget.prototype = {
	
	addPieChart : function(watchedDataset, watchedColumn, selectionFunction){
		var newPieChart = new PieChart(this, watchedDataset, watchedColumn, selectionFunction);
		this.pieCharts.push(newPieChart);
		if (	(typeof GeoTemConfig.datasets !== "undefined") && 
				(GeoTemConfig.datasets.length > watchedDataset) )
			newPieChart.initPieChart(GeoTemConfig.datasets);
		this.redrawPieCharts();
	},

	initWidget : function(data) {
		this.datasets = data;
		
		this.gui.refreshColumnSelector();
		
		$(this.pieCharts).each(function(){
			if (this instanceof PieChart)
				this.initPieChart(data);
		});
	},
	
	redrawPieCharts : function(objects, overwrite) {
		$(this.pieCharts).each(function(){
			if (this instanceof PieChart){
				if ( (typeof overwrite !== "undefined") && overwrite)
					this.preHighlightObjects = objects;
				this.redrawPieChart(objects);
			}
		});
	},

	highlightChanged : function(objects) {
		if( !GeoTemConfig.highlightEvents ){
			return;
		}
		if ( (typeof objects === "undefined") || (objects.length == 0) ){
			return;
		}
		this.redrawPieCharts(objects, false);
	},

	selectionChanged : function(selection) {
		if( !GeoTemConfig.selectionEvents ){
			return;
		}
		if (!selection.valid()){
			selection.loadAllObjects();
		}
		var objects = selection.objects;
		this.redrawPieCharts(objects, true);
	},
};