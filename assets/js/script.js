function changeDateSQLFormat(olddate){


        return  olddate.substring(olddate.lastIndexOf("/")+1,olddate.length) + "-" + olddate.substring(olddate.indexOf("/")+1,olddate.lastIndexOf("/")) + "-" + olddate.substring(0,olddate.indexOf("/"));
}



  // function isstringint(str){
  //   if(str){
  //     try {
  //         intValue = Integer.parseInt(string);
  //         return true;
  //     }catch (NumberFormatException e) {
  //         System.out.println("Input String cannot be parsed to Integer.");
  //     }
  //   }
  // }

  // function stringsplice(str,ind,upto){
  //   str.splice(ind,upto));
  // }


//     function isstringNumeric(str) {
//       var intValue;
		
//     System.out.println(String.format("Parsing string: \"%s\"", str));
		
//     if(str == null || str.equals("")) {
//         System.out.println("String cannot be parsed, it is null or empty.");
//         return false;
//     }
    
//     try {
//         intValue = Integer.parseInt(str);
//         return true;
//     } catch (NumberFormatException e) {
//         System.out.println("Input String cannot be parsed to Integer.");
//     }
//     return false;
// }


  function pnotifyMessage(Message,type){
	  new PNotify({	title: type,
						text: Message,
						type: type,
						styling: 'bootstrap3',
						before_close: function(PNotify) {
							PNotify.update({
								title: PNotify.options.title + " - Enjoy your Stay",
								before_close: null
							});

							PNotify.queueRemove();
							return false;
						}
					});
  }


  // function sortarrbydate(arr,dateparam){
  //   arr.sort(function(a,b){
  //     // Turn your strings into dates, and then subtract them
  //     // to get a value that is either negative, positive, or zero.
  //     console.log(new Date(a["dateparam"]) - new Date(b["dateparam"]));
  //     return new Date(a["dateparam"]) - new Date(b["dateparam"]);
  //   });
  // }

  
  function sortarrbydate(arr){
    arr.sort(function(a,b){
      // Turn your strings into dates, and then subtract them
      // to get a value that is either negative, positive, or zero.
      return new Date(a.date) - new Date(b.date);
    });
  }



  function sortarrbydatedb(arr,dateparam){
    arr.sort(function(a,b){
      // Turn your strings into dates, and then subtract them
      // to get a value that is either negative, positive, or zero.
      console.log(a[dateparam],b[dateparam],new Date(a[dateparam]) - new Date(b[dateparam]));
      return new Date(a[dateparam]) - new Date(b[dateparam]);
    });
  }


  function sortarr(arr,param,order){
    // const points = [40, 100, 1, 5, 25, 10];
    if(order == "asc"){
      return arr.sort(function(a, b){return a[param] - b[param]});
    }else if(order == "desc"){
      return arr.sort(function(a, b){return b[param] - a[param]});
    }
  }


  // program to convert first letter of a string to uppercase
function capitalizeFirstLetter(str) {

  // converting first letter to uppercase
  const capitalized = str.charAt(0).toUpperCase() + str.slice(1);

  return capitalized;
}


function capitalizeString(str) {
  return str.replace(/^(.)|\s+(.)/g, c => c.toUpperCase());
}


function finddot(str){
  var pos = 0;
  pos = str.indexOf(".");
  // console.log(str,pos);
  if(pos > 0){
    return str.slice(0,pos - 1);
  }else{
    return str;
  }
}


function getuniques(arr){
  var uniqueNames = [];
  $.each(arr, function(i, el){
      if($.inArray(el, uniqueNames) === -1) uniqueNames.push(el);
  });
  return uniqueNames;
}


function changeDateUserFormat(olddate){
//2017-10-05
  if(olddate != "0000-00-00"){
    return  olddate.substring(olddate.lastIndexOf("-")+1,olddate.length) + "/" + olddate.substring(olddate.indexOf("-")+1,olddate.lastIndexOf("-")) + "/" + olddate.substring(0,olddate.indexOf("-"));
  }
		
}


function changeDateTimeSqlFormat(dateString){
//2017-10-05
  if(dateString != "0000-00-00"){
    // const dateParts = dateString.split("/");
    // const timeParts = dateParts[2].split(" ");
    // const newDateString = `${timeParts[0]}-${dateParts[1]}-${dateParts[0]} ${timeParts[1]}`;
    // const sqlDate = new Date(newDateString).toISOString().slice(0, 19).replace('T', ' ');
    // return sqlDate;    
    
    const dateParts = dateString.split(" ");
    const datePart = dateParts[0].split("/").reverse().join("-");
    const timePart = dateParts[1];
    // const newDateString = `${datePart} ${timePart}`;
    // const jsDate = new Date(newDateString);
    // const formattedDate = jsDate.toISOString().slice(0, 19).replace('T', ' ');
    const newDateString = `${datePart} ${timePart}`;
    // const jsDate = new Date(newDateString + "+05:30"); // adjust the time zone offset as needed
    // const formattedDate = jsDate.toISOString().replace('T', ' ').slice(0, 19);

    console.log(newDateString); // outputs "26/04/2023 18:43:19"
    return newDateString; // outputs "26/04/2023 18:43:19"

  }
		
}


function changeDateTimeUserFormat(dateString){
//2017-10-05
  if(dateString != "0000-00-00"){
    const datetime = new Date(dateString);

    const dateStr = datetime.toLocaleDateString('en-GB', {day: '2-digit', month: '2-digit', year: 'numeric'});
    const timeStr = datetime.toLocaleTimeString('en-GB', {hour: '2-digit', minute: '2-digit', second: '2-digit'});

    const userDateTimeStr = `${dateStr} ${timeStr}`;
    // console.log(userDateTimeStr);
    return userDateTimeStr;
  }
		
}



// function changeDateTimeUserFormat(dateString){
//   //2017-10-05
//     if(dateString != "0000-00-00"){
//       const dateParts = dateString.split(" ");
//       const datePart = dateParts[0].split("-").reverse().join("/");
//       const timePart = dateParts[1];
//       // const newDateString = `${datePart} ${timePart}`;
//       // const jsDate = new Date(newDateString);
//       // const formattedDate = jsDate.toISOString().slice(0, 19).replace('T', ' ');
      
//       const userDateTimeStr = `${datePart} ${timePart}`;
//       return userDateTimeStr;
//     }
      
//   }




Array.prototype.remove = function(from, to) {
  console.log(this);
		var rest = this.slice((to || from) + 1 || this.length);
		this.length = from < 0 ? this.length + from : from;
		return this.push.apply(this, rest);
	};
	
	
	var busy = function(regionSelector){
  $('input ',regionSelector).addClass('busy').attr('disabled','disabled');
  $('button',regionSelector).attr('disabled','disabled');
  $('select',regionSelector).addClass('busy').attr('disabled','disabled');
  $('a',regionSelector).attr('disabled','disabled').on('click',function(event){
      event.preventDefault();
  })
}

var ready = function(regionSelector){
  $('input',regionSelector).removeClass('busy').removeAttr('disabled');
  $('button',regionSelector).removeAttr('disabled');
  $('select',regionSelector).removeClass('busy').removeAttr('disabled');
  $('a',regionSelector).removeAttr('disabled').off(); 
}
 


function LoadingAnim(id,action){
  if(id){
    if(action == "show"){
      $("."+id).fadeIn("slow");
    }else if(action == "hide"){
      $("."+id).fadeOut("slow");
    }
  }
}

function blockInput(){
  $(":input").prop("disabled", true);
  $("a").css("cursor","arrow").click(false);
}

function releaseInput(){
  $(":input").prop("disabled", false);
  $("a").unbind(false);
}


function popupAnim (popupname,action){
	if(action == "open"){
		document.getElementsByClassName(popupname)[0].classList.add("active");
	}else if(action == "close"){
		document.getElementsByClassName(popupname)[0].classList.remove("active");
	}
}


function popupAnim1 (popupname,action){
	if(action == "open"){
		document.getElementsByClassName(popupname)[0].classList.add("active");
	}else if(action == "close"){
		document.getElementsByClassName(popupname)[0].classList.remove("active");
	}
}



function indianPriceFormat(x){
var res =0;
if(x){
x=x.toString();
var afterPoint = '';
if(x.indexOf('.') > 0)
   afterPoint = x.substring(x.indexOf('.'),x.length);
x = Math.floor(x);
x=x.toString();
var lastThree = x.substring(x.length-3);
var otherNumbers = x.substring(0,x.length-3);
if(otherNumbers != '')
    lastThree = ',' + lastThree;
 res = otherNumbers.replace(/\B(?=(\d{2})+(?!\d))/g, ",") + lastThree + afterPoint;
}
return res;
}


  function getMonthName(n){
    var months = ["January","February","March","April","May","June","July","August","September","October","November","December"];
    for(i = 0; i<months.length;i++){
      if(Number(n)-1 == i ){
        return months[i];
      }
    }
  }

  function todayDate (){
    //getting today's date
    var today = new Date();
    var dd = String(today.getDate()).padStart(2,'0');
    var mm = String(today.getMonth()+ 1).padStart(2,'0');
    var yyyy = today.getFullYear();
    today = dd + '/' + mm + '/' + yyyy;
    return today;
  }


  function ordinal(n) {
        var s = ["th", "st", "nd", "rd"];
        var v = n%100;
        return n + (s[(v-20)%10] || s[v] || s[0]);
      }
    

  
function getWholePercent(percentOf,percentFor)
{
    return Math.floor(percentOf*percentFor/100);
   
}


function changedNuminput(e){
  // console.log(e);
  if(e >= 48 && e <= 57){
    return true;
  }else if(e == 8){ //backspace
    return true;
  }else if(e == 127){ //delete
    return true;
  }else if(e == 46){ //delete mini
    return true;
  }else{
    return false;
  }
}

function datediff(date1, date2 ) {
  
  date1=new Date(changeDateSQLFormat(date1));
  date2=new Date(changeDateSQLFormat(date2));
   //Get 1 day in milliseconds
   var one_day=1000*60*60*24;
 
   // Convert both dates to milliseconds
   var date1_ms = date1.getTime();
   var date2_ms = date2.getTime();
 
   // Calculate the difference in milliseconds
   var difference_ms = date2_ms - date1_ms;
     
   // Convert back to days and return
   return Math.round(difference_ms/one_day); 
 
 }

function daysInMonth (month, year) {   
    return new Date(year, month, 0).getDate();
    
}


function isAuthorisedUrl(url,role){
var commonUrl = ["/dashboard","/lrcreation","/lr/:id","/lredit/:abc","/lrprint/:abc","/lrprint/:abc/:url","/createcustomer","/customers","/customer/:customerId","/editcustomer/:customerId","/customeronaccount/:gst","/onaccountcustomers","/lr","/lrfilter","/pending","/listrrstatement","/createrrstatement","/rrstatement/:statementid","/waybilldeclaration/:statementid","export","/ctd","/profile","/commands","/changepassword","/loadingsheet/:memoid"];
if(role == 1 || commonUrl.indexOf(url)>=0){
return true;
}
return false;
}


function getUserDateTimeFormat(dt){
if(dt){
var temp =new Date(dt);
var median = "PM";
if(temp.getHours()<12){
median = "AM";
}

return changeDateUserFormat(temp.getFullYear()+"-"+(temp.getMonth()+1)+"-"+temp.getDate()) +" " +temp.getHours()%12 + ":"+temp.getMinutes() + ":" +temp.getSeconds() + median;
;
}
return "";
}

function setNextFocus(e,el,next,key,arr1,arr2){
  if (e.which == 13) {
        if(el == "commodities"){
            if(next >= arr1.commodities.length){
              e.preventDefault();
            }else{
                e.preventDefault();
                $("#" + key + next).focus();
            }
        }else{
            e.preventDefault();
            $("#" + next).focus();
        }


  }else if (e.which == 9) {




  }else if(e.which == 32 && key == "checkbox"){
    
  }
}




function genereatepdf(pageid,filename){
  const element = document.getElementById(pageid);
  const noprint = document.getElementById("noprint");

  noprint.style.display = "none";

  html2pdf()
  .from(element)
  .save(filename)
  .setPageSize("A4")
  .then(function(){
    noprint.style.display = "block";
  });


}



function presskey(keyname){
  let keyEvent = new KeyboardEvent('keypress',{key:keyname});
  document.body.dispatchEvent(keyEvent);
  // console.log(keyEvent);
}




function focusonTime(id,time){
	if(!time){
		time = 500;
	}
  console.log(time);
	setTimeout(()=> {
		$('#'+id).focus();
	},time)
}


function setArrow(e,t){
  if(e.code == "ArrowRight" || e.code == "ArrowLeft"){
      if(e.code == "ArrowRight"){
        arrow++;
    }
    if(e.code == "ArrowLeft"){
        arrow--;
    }
    if(arrow == 3){arrow = 2;}
    if(arrow == -1){arrow = 0;}
    var input = t;
      var s = input.value;console.log(s);
      if(arrow == 0) {from = 0;to = s.indexOf("/");}
    if(arrow == 1) {from = s.indexOf("/")+1; to =getPosition(s,"/",2);}
    if(arrow == 2) {from = getPosition(s,"/",2)+1; to =s.length;}
          window.setTimeout(function() {
              input.setSelectionRange(from, to);
        input.focus();
          }, 0);
      }
  }
  
  function initArrow (e){
  var arrow = 0;
  
    var input = e;
      var s = input.value;
       window.setTimeout(function() {
              input.setSelectionRange(0, s.indexOf("/"));
        input.focus();
          }, 0);
  }
  

  function setProperValue(t){
    if(t){
      var val = t.value;
      spdata = val.split("/");
      if(spdata[0].length <2){spdata[0] = "0" + spdata[0];}
      if(spdata[1].length <2){spdata[1] = "0" + spdata[1];}
      if(spdata[2].length == 1){spdata[2] = "200" + spdata[2];}
      if(spdata[2].length == 2){spdata[2] = "20" + spdata[2];}
      if(spdata[2].length == 3){spdata[2] = "2" + spdata[2];}
      t.value = spdata.join("/");
      //}
      arrow=0;
    }
  
    
  }

  function chckboxfocus(cboxval,next){
    if(cboxval == 1){
      focusInput(next,150);
    }
  }

function focusInput(idd,timee){
  if(!timee){
    timee = 500;
  }
  setTimeout(() => {$('#' + idd).focus() },timee);
}


function setDateFilter(filterdata){
var date ;
 if (filterdata.from_date && filterdata.to_date) {
            date = {
                "op": "Between",
                "value": changeDateSQLFormat(filterdata.from_date),
                "value1": changeDateSQLFormat(filterdata.to_date)
            };
        } else {
            if (filterdata.from_date) {
                date = {
                    "op": ">=",
                    "value": changeDateSQLFormat(filterdata.from_date)
                };
            }
            if (filterdata.to_date) {
                date = {
                    "op": "<=",
                    "value": changeDateSQLFormat(filterdata.to_date)
                };
            }
        }
return date;
}


function onLinkClick(idname) {
  const element = document.getElementById(idname);
  element.scrollIntoView();
}



//validating gst number

function isValidGSTNumber(gstin){
    if(gstin){
    
     gstin = gstin.toUpperCase();
      if (gstin.length != 15) {
        return false;
      }
      if (validatePattern(gstin)) {
        return (gstin[14] == calcCheckSum(gstin.toUpperCase()));
      }
      return false;
    }
    return true;
    }

function validatePattern(gstin) {
    'use strict';
    var gstinRegexPattern = /^([0-2][0-9]|[3][0-7])[A-Z]{3}[ABCFGHLJPTK][A-Z]\d{4}[A-Z][A-Z0-9][Z][A-Z0-9]$/;
    return gstinRegexPattern.test(gstin); // Regex validation result GSTIN of 15 digits.
  }
  
  function calcCheckSum(gstin) {
      var GSTN_CODEPOINT_CHARS = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ',
      factor = 2,
      sum = 0,
      checkCodePoint = 0,
      mod = GSTN_CODEPOINT_CHARS.length,
      i;
  
    for (i = gstin.length - 2; i >= 0; i--) {
      var codePoint = -1;
      for (var j = 0; j < GSTN_CODEPOINT_CHARS.length; j++) {
        if (GSTN_CODEPOINT_CHARS[j] == gstin[i]) {
          codePoint = j;
        }
      }
      var digit = factor * codePoint;
      factor = (factor == 2) ? 1 : 2;
      digit = Math.floor(digit / mod) + (digit % mod);
      sum += digit;
    }
    checkCodePoint = (mod - (sum % mod)) % mod;
    return GSTN_CODEPOINT_CHARS[checkCodePoint];
  }
  //validating gst nmber ends
  
  // function isNumeric(str) {
  //   if (typeof str != "string") return false // we only process strings!  
  //   return !isNaN(str) && // use type coercion to parse the _entirety_ of the string (`parseFloat` alone does not do this)...
  //          !isNaN(parseFloat(str)) // ...and ensure strings of whitespace fail
  // }

  // function pmnames(str){
  //   str = str.toLocaleLowerCase();
  //   names = ["rashi","dad","ac","sowji","my","bank","g.p"];
  //   for (var i = 0; i < names.length; i++) {
  //     // console.log(str,names[i],str.indexOf(names[i]));
  //     if(str.indexOf(names[i]) >= 0){
  //       // console.log(str,names[i],str.indexOf(names[i]));
  //       return true;
  //     }
  //   }
  //   return false;
  // }


  // function txtbrackets(str){
  //   // var testString = "(Charles) de (Gaulle), (Paris) [CDG]"
  //   var reBrackets = /\((.*?)\)/g;
  //   var listOfText = [];
  //   var found;
  //   while(found = reBrackets.exec(str)) {
  //     listOfText.push(found[1]);
  //   };
  //   return listOfText;
  // }

  // function singlenum(arr){
  //   var rtnarr = {num: 0,number:[],nan:[]};var temparr = [];
  //   if(arr && arr.length){
  //     for(k=0;k<arr.length;k++){
  //       if(!isNaN(arr[k])){
  //         rtnarr.num += 1;
  //         rtnarr.number.push(arr[k]);
  //       }else{
  //         // console.log(arr[k]);
  //         if(arr[k].indexOf('-') >=0 || arr[k].indexOf(',') >=0 ){
  //           (arr[k].indexOf('-') >=0)?temparr = arr[k].split('-'):"";
  //           (arr[k].indexOf(',') >=0)?temparr = arr[k].split(','):"";
  //           // console.log(temparr);
  //           if(temparr && temparr.length){
  //             for(j=0;j<temparr.length;j++){
  //               // console.log(temparr[j]);
  //               var isnum = true;
  //               if(isNaN(temparr[j])){
  //                 isnum = false;
  //               }
  //             }
  //             if(isnum){
  //               rtnarr.num += 1;
  //               rtnarr.number.push(arr[k]);
  //             }else{
  //               rtnarr.nan.push(arr[k]);
  //             }
  //           }
  //         }else{
  //           rtnarr.nan.push(arr[k]);
  //         }
  //       }
  //     }
  //     // console.log(rtnarr);
  //   }else{
  //     rtnarr = {num:0,number:[]};
  //   }
  //     return rtnarr;
  // }
  

  
function isNumeric(str) {
  if (typeof str != "string") return false // we only process strings!  
  return !isNaN(str) && // use type coercion to parse the _entirety_ of the string (`parseFloat` alone does not do this)...
         !isNaN(parseFloat(str)) // ...and ensure strings of whitespace fail
}

function pmnames(str){
  str = str.toLocaleLowerCase();
  names = ["rashi","dad","ac","sowji","my","bank","g.p"];
  for (var i = 0; i < names.length; i++) {
    console.log(str,names[i],str.indexOf(names[i]));
    if(str.indexOf(names[i]) >= 0){
      console.log(str,names[i],str.indexOf(names[i]));
      return true;
    }
  }
  return false;
}


function txtbrackets(str){
  // var testString = "(Charles) de (Gaulle), (Paris) [CDG]"
  var reBrackets = /\((.*?)\)/g;
  var listOfText = [];
  var found;
  while(found = reBrackets.exec(str)) {
    listOfText.push(found[1]);
  };
  return listOfText;
}

function singlenum(arr){
  var rtnarr = {num: 0,number:[],nan:[]};var temparr = [];
  if(arr && arr.length){
    for(k=0;k<arr.length;k++){
      if(!isNaN(arr[k])){
        rtnarr.num += 1;
        rtnarr.number.push(arr[k]);
      }else{
        // console.log(arr[k]);
        if(arr[k].indexOf('-') >=0 || arr[k].indexOf(',') >=0 ){
          (arr[k].indexOf('-') >=0)?temparr = arr[k].split('-'):"";
          (arr[k].indexOf(',') >=0)?temparr = arr[k].split(','):"";
          // console.log(temparr);
          if(temparr && temparr.length){
            for(j=0;j<temparr.length;j++){
              // console.log(temparr[j]);
              var isnum = true;
              if(isNaN(temparr[j])){
                isnum = false;
              }
            }
            if(isnum){
              rtnarr.num += 1;
              rtnarr.number.push(arr[k]);
            }else{
              rtnarr.nan.push(arr[k]);
            }
          }
        }else{
          rtnarr.nan.push(arr[k]);
        }
      }
    }
    // console.log(rtnarr);
  }else{
    rtnarr = {num:0,number:[]};
  }
    return rtnarr;
}




  function validateWayBill(ewaybill){
	  var temp = [];
        if (ewaybill) {
            temp = ewaybill.trim().split(",");
        }
        for (var i = 0; i < temp.length; i++) {
            if (temp[i].length != 12) {
                return false;
            }
        }
        return true;
  }



 