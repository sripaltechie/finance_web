app.factory("Data", ['$http', 'toaster',
    function ($http, toaster) { // This service connects to our REST API
 
        var serviceBase = 'api/v1/';
 
        var obj = {};
        obj.toast = function (data) {
            toaster.pop(data.status, "", data.message, 10000, 'trustedHtml');
        }

        obj.mkeobj = function(msg,status){
            obj.message = msg;
            obj.status = status
            return obj;
        }

        obj.get = function (q,qs) {
          var data = { url : serviceBase + q ,method:"GET",params : qs};
            return $http(data).then(function (results) {
                return results.data;
            });
        };
       /* obj.post = function (q, object) {
            return $http.post(serviceBase + q, object).then(function (results) {
                return results.data;
            });
        };*/
		obj.post = function (q, object) {
            console.log(object);
		$("#firmData :input").prop("disabled", true);
			var xsrf = $.param(object);
            //  console.log({url:serviceBase + q, method: "POST",data:xsrf,headers: {'Content-Type': 'application/x-www-form-urlencoded'}});
            return $http( {url:serviceBase + q, method: "POST",data:xsrf,headers: {'Content-Type': 'application/x-www-form-urlencoded'}}).then(function (results) {
			$("#firmData :input").prop("disabled", false);
                return results.data;
            });
        };
        obj.put = function (q, object) {
		$("#firmData :input").prop("disabled", true);
            return $http.put(serviceBase + q, object).then(function (results) {
			$("#firmData :input").prop("disabled", false);
                return results.data;
				
            });
        };
        obj.delete = function (q) {
            return $http.delete(serviceBase + q).then(function (results) {
                return results.data;
            });
        };
 
        return obj;
}]);