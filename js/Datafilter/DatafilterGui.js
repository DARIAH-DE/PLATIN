/*
* DatafilterGui.js
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
 * @class DatafilterGui
 * Datafilter GUI Implementation
 * @author Kathrin Marquardt (Kathrin.Marquardt@sbb.spk-berlin.de)
 *
 * @param {DatafilterWidget} parent Datafilter widget object
 * @param {HTML object} parentEle parent HTML element to append the Datafilter gui
  */
function DatafilterGui(datafilterWidg, parentEle) {

    this.parent = datafilterWidg;
    var datafilterGui = this;

    this.datafilterContainer = parentEle;

    var optCount = this.parent.dataFilterOpt.length;
    var linguaIndex = 1;
    if (GeoTemConfig.language != this.parent.labelLang)
    {
        linguaIndex = 3;
    }

    if (optCount > 0) {

        // Complete wrapper for filter elements
        this.coverDiv = document.createElement("div");
        var applyIndex = datafilterPrevSiblingCount;

            // Filter symbol/icon to advise selection
        var symbolDiv = document.createElement("div");
        symbolDiv.innerHTML = '<span style="color: #fafafa; margin-left:10px; margin-right: 10px; ">' +
            '<i class="fas fa-filter"></i>' +
            '</span>';
        symbolDiv.style.cssFloat = 'left';
        this.coverDiv.appendChild(symbolDiv);
        this.fieldsDiv = document.createElement("div");
        this.fieldsDiv.style.cssFloat = 'left';
        this.coverDiv.appendChild(this.fieldsDiv);

        for (var i = 0; i < optCount; i++) {
            var jsonFeld = this.parent.dataFilterOpt[i][0];
            var anzahl = this.parent.options[jsonFeld].length;

            colDiv = document.createElement("div");
            colDiv.style.cssFloat = 'left';
            colDiv.style.marginRight = '15px';
            //colDiv.style.marginRight = '10px';
            //colDiv.style.borderRight = '1px solid #fff';
            //colDiv.style.paddingRight = '7px';
            //colDiv.style.paddingTop = '2px';
            colDiv.style.paddingBottom = '3px';
            this.fieldsDiv.appendChild(colDiv);

            colThDiv = document.createElement("div");
            colThDiv.style.fontSize = '13px';
            colThDiv.textContent = this.parent.dataFilterOpt[i][linguaIndex];
            colDiv.appendChild(colThDiv);

            colTdDiv= document.createElement("div");
            colTdDiv.style.paddingTop = '2px';
            selectEle = document.createElement("select");
            selectEle.name = jsonFeld;
            selectEle.id = jsonFeld;
            selectEle.style.height = '23px';  // td-Elements 25px = 23px + 2px (colTdDiv.style.paddingTop)
            selectEle.style.fontSize = '13px';
            selectEle.style.borderRadius = '0px';
            selectEle.id = jsonFeld;
            selectEle.name = jsonFeld;
            selectEle.style.lineHeight = '14px';
            selectEle.style.marginBottom = '0px';
            selectEle.style.paddingBottom = '0px';
            selectEle.style.paddingTop = '0px';
            colTdDiv.appendChild(selectEle);
            colDiv.appendChild(colTdDiv);

            var optionEle = document.createElement("option");
            optionEle.value = '-1';
            optionEle.textContent = '*';
            selectEle.appendChild(optionEle);

            for (var j = 0; j < anzahl; j++) {
//                console.log(parent.options[jsonFeld][j]);
                optionEle = document.createElement("option");
                optionEle.value = this.parent.options[jsonFeld][j].value;
                if (this.parent.options[jsonFeld][j].label_de)
                {
                    if (linguaIndex == 1) {
                        optionEle.textContent = this.parent.options[jsonFeld][j].label_de;
                    }
                    else
                    {
                        optionEle.textContent = this.parent.options[jsonFeld][j].label_iso;
                    }
                }
                else
                {
                    optionEle.textContent = this.parent.options[jsonFeld][j].label;
                }
                selectEle.appendChild(optionEle);
            }

            selectEle.onchange = function () {
                datafilterGui.parent.filtering();
            }
        }

        if (this.datafilterContainer.nodeName == 'DIV')
        {
            if (this.datafilterContainer.childElementCount < applyIndex)
            {
                applyIndex = this.datafilterContainer.childElementCount;
            }
            this.datafilterContainer.insertBefore(this.coverDiv, this.datafilterContainer[applyIndex]);
        }

        if (this.datafilterContainer.nodeName == 'TABLE')
        {
            var zeile = this.datafilterContainer.rows[0];
            if (zeile.length < applyIndex)
            {
                applyIndex = zeile.length;
            }

            var cellFilter = zeile.insertCell(applyIndex);
            cellFilter.rowSpan = this.datafilterContainer.rows.length;
            cellFilter.appendChild(this.coverDiv);
        }
    }
}
