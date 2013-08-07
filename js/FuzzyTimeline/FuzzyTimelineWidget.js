/*
* FuzzyTimelineWidget.js
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
 * @class FuzzyTimelineWidget
 * FuzzyTimelineWidget Implementation
 * @author Sebastian Kruse (skruse@mpiwg-berlin.mpg.de)
 *
 * @param {WidgetWrapper} core wrapper for interaction to other widgets
 * @param {HTML object} div parent div to append the FuzzyTimeline widget div
 * @param {JSON} options user specified configuration that overwrites options in FuzzyTimelineConfig.js
 */
function FuzzyTimelineWidget(core, div, options) {

	this.datasets;
	this.hiddenDatasets;
	this.shownDatasets;
	this.overallMin;
	this.overallMax;
	
	this.core = core;
	this.core.setWidget(this);

	this.options = (new FuzzyTimelineConfig(options)).options;
	this.gui = new FuzzyTimelineGui(this, div, this.options);
	
	this.density;
	this.rangeSlider;
	this.rangeBars;
}

FuzzyTimelineWidget.prototype = {

	initWidget : function(data) {
		var fuzzyTimeline = this;
		
		$(fuzzyTimeline.gui.densityDiv).empty();
		delete fuzzyTimeline.density;
		$(fuzzyTimeline.gui.rangeTimelineDiv).empty();
		$(fuzzyTimeline.gui.rangePiechartDiv).empty();
		$(fuzzyTimeline.gui.sliderDiv).empty();
		delete fuzzyTimeline.rangeSlider;
		delete fuzzyTimeline.rangeBars;
		
		
		if ( (data instanceof Array) && (data.length > 0) )
		{
			fuzzyTimeline.density = new FuzzyTimelineDensity(fuzzyTimeline,fuzzyTimeline.gui.densityDiv);
			fuzzyTimeline.rangeSlider = new FuzzyTimelineRangeSlider(fuzzyTimeline);
			fuzzyTimeline.rangeBars = new FuzzyTimelineRangeBars(fuzzyTimeline);
			
			fuzzyTimeline.datasets = data;
			
			$(fuzzyTimeline.datasets).each(function(){
				$(this.objects).each(function(){
					var datemin,datemax;
					if (this.isTemporal){
						//TODO: allow more than one date
						datemin = moment(this.dates[0].date);
						datemax = datemin;
					} else if (this.isFuzzyTemporal){
						//TODO: allow more than one date
						datemin = this.TimeSpanBegin;
						datemax = this.TimeSpanEnd;
					}
					
					if (typeof fuzzyTimeline.overallMin === "undefined")
						fuzzyTimeline.overallMin = datemin;
					if (typeof fuzzyTimeline.overallMax === "undefined")
						fuzzyTimeline.overallMax = datemax;
					
					if (fuzzyTimeline.overallMin > datemin)
						fuzzyTimeline.overallMin = datemin;
					if (fuzzyTimeline.overallMax < datemax)
						fuzzyTimeline.overallMax = datemax;
				});
			});
			
			fuzzyTimeline.density.initialize(fuzzyTimeline.datasets);
			fuzzyTimeline.rangeBars.initialize(fuzzyTimeline.datasets);
			fuzzyTimeline.rangeSlider.initialize(fuzzyTimeline.datasets);
		}
	},
	
	slidePositionChanged : function(spanWidth, shownDatasets, hiddenDatasets) {
		var fuzzyTimeline = this;
		//redraw density plot
		fuzzyTimeline.density.initialize(shownDatasets);
		//redraw range plot
		fuzzyTimeline.rangeBars.drawRangeBarChart(shownDatasets,spanWidth);
		//redraw pie charts
		fuzzyTimeline.rangeBars.drawRangePieChart(shownDatasets,hiddenDatasets);
	},

	highlightChanged : function(objects) {
		if( !GeoTemConfig.highlightEvents ){
			return;
		}
		if ( (typeof objects === "undefined") || (objects.length == 0) ){
			return;
		}
		this.density.highlightChanged(objects);
		this.rangeBars.highlightChanged(objects);
	},

	selectionChanged : function(selection) {
		if( !GeoTemConfig.selectionEvents ){
			return;
		}
		var objects = selection.objects;
		this.density.selectionChanged(objects);
		this.rangeBars.selectionChanged(objects);
	},
	
	getTicks : function(dataObject, spanWidth) {
		var datemin,datemax;
		if (dataObject.isTemporal){
			datemin = moment(dataObject.dates[0].date);
			datemax = datemin;
		} else if (dataObject.isFuzzyTemporal){
			datemin = dataObject.TimeSpanBegin;
			datemax = dataObject.TimeSpanEnd;
		} else{
			return;
		}
		
		var firstTick = Math.floor((datemin-this.overallMin)/spanWidth);
		var lastTick = Math.floor((datemax-this.overallMin)/spanWidth);
		//calculate how much the first (and last) tick and the time-span overlap
		var firstTickPercentage = 1;
		var lastTickPercentage = 1;
		if (firstTick != lastTick){
			var secondTickStart = this.overallMin+(firstTick+1)*spanWidth;
			var lastTickStart = this.overallMin+lastTick*spanWidth;
			firstTickPercentage = (secondTickStart-datemin)/spanWidth;
			lastTickPercentage = (datemax-lastTickStart)/spanWidth;
		}
		
		return({	firstTick:firstTick,
					lastTick:lastTick,
					firstTickPercentage:firstTickPercentage,
					lastTickPercentage:lastTickPercentage});
	},

	getObjects : function(date) {
		var fuzzyTimeline = this;
		var searchDate = moment(date);
		
		var datasets = [];		
		$(fuzzyTimeline.datasets).each(function(){
			var objects = [];
			//check if we got "real" datasets, or just array of objects
			var datasetObjects = this;
			if (typeof this.objects !== "undefined")
				datasetObjects = this.objects;
			$(datasetObjects).each(function(){
				var datemin,datemax;
				var dataObject = this;
				if (dataObject.isTemporal){
					datemin = moment(dataObject.dates[0].date);
					datemax = datemin;
				} else if (dataObject.isFuzzyTemporal){
					datemin = dataObject.TimeSpanBegin;
					datemax = dataObject.TimeSpanEnd;
				} else{
					return;
				}
				
				if ( (datemin <= searchDate) && (datemax >= searchDate) )
					objects.push(this);
			});
			datasets.push(objects);
		});

		return(datasets);
	},
};