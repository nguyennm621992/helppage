/**
 * HOMER - Responsive Admin Theme
 * Copyright 2015 Webapplayers.com
 *
 */
var isDefined = angular.isDefined,
        isFunction = angular.isFunction,
        isString = angular.isString,
        isObject = angular.isObject,
        isArray = angular.isArray,
        forEach = angular.forEach,
        extend = angular.extend,
        equals = angular.equals,
        copy = angular.copy,
        ngElement = angular.element,
        httpBlockConfig = {block: true},
        movingMenu = false,
        cursor,
        sessionStorageEnabled = ("sessionStorage" in window) ? true : false;
//get current language of browser
var browserLang = navigator.language || navigator.userLanguage;
browserLang = browserLang.split('-')[0];
//Support function
var getItemById = function (list, id) {
    var result = $.grep(list, function (e) {
        return e.id == id;
    });
    if (result.length > 0) {
        return result[0];
    } else {
        return null;
    }
};
var getTreeItemById = function (tree, id) {
    forEach(tree, function (val, key) {
        if (val.id == id) {
            cursor = val;
            return;
        } else if (isDefined(val.children)) {
            getTreeItemById(val.children, id);
        }
    });
    return null;
};
var mine;

var baseConfig = (function () {
    var config = {};
    config.appName = 'help';
    config.apiAbsUrl = 'help';
    config.apiUrl = '..';
    config.baseUrl = '.';
    config.langApi = [config.apiUrl, 'help/help_page/help_lang'].join('/');
    config.loginUrl = '/sign';
    config.mainState = [config.appName, 'main'].join('.');
    config.tplPath = [config.baseUrl, 'template'].join('/');

    config.getModuleConfig = function (category) {
        return {
            name: category,
            prefix: [category, '_'].join(''),
            moduleName: (category == 'sign') ? category : [config.appName, category].join('.'),
            tplPath: [config.tplPath, category].join('/'),
            apiUrl: [config.apiUrl, category].join('/')
        };
    };
    return config;
}());

(function () {
    var dependencyInjector = [
        'ui.router', // Angular flexible routing
        'ngSanitize', // Angular-sanitize
        'ui.bootstrap', // AngularJS native directives for Bootstrap
        'ngAnimate', // Angular animations
        'summernote', // Summernote plugin
        'ui.sortable'
    ];

    var module_list = ['menu'];
    forEach(module_list, function (value, key) {
        var moduleConfig = baseConfig.getModuleConfig(value);
        dependencyInjector.push(moduleConfig.moduleName);
    });


    var baseController = 'baseController';
    var module = angular.module('help', dependencyInjector);

    module.config(['$stateProvider', '$urlRouterProvider', '$httpProvider', function ($stateProvider, $urlRouterProvider, $httpProvider) {
            $urlRouterProvider.otherwise('menu/' + browserLang + '/');
            $stateProvider
                    .state('help', {
                        resolve: {
                            lang: ['langService', function (langService) {
                                    return langService.reload('helppage');
                                }],
                            globalConfig: ['commonService', function (commonService) {
                                    return commonService.getGlobalConfig();
                                }],
                            menuTree: ['treeService', function (treeService) {
                                    return treeService.getTree();
                                }],
                            headTitle: ['treeService', function (treeService) {
                                    return treeService.getTitle();
                                }]
                        },
                        url: "/menu/:langCode",
                        templateUrl: "template/main.html",
                        data: {
                            pageTitle: 'GROUPWARE',
                            specialClass: 'landing-page'
                        },
                        controller: baseController
                    });

            // Use x-www-form-urlencoded Content-Type && X_REQUESTED_WITH
            $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';
            $httpProvider.defaults.headers.put['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';
            $httpProvider.defaults.headers.common['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';
            $httpProvider.defaults.headers.common['X_REQUESTED_WITH'] = 'XMLHttpRequest';
            $httpProvider.defaults.headers.common["If-Modified-Since"] = "0";
            $httpProvider.defaults.withCredentials = true;

            /*
             * Ajax Response interceptor
             */
            $httpProvider.interceptors.push(['$q', '$rootScope', function ($q, $rootScope) {
                    return {
                        'request': function (config) {
                            if (config.hasOwnProperty('block') && config.block) {
                                $rootScope.isBlocked = true;
                            }
                            config.requestTimestamp = new Date().getTime();
                            return config;
                        },
                        'requestError': function (response) {
                            return $q.reject(response);
                        },
                        'response': function (response) {
                            if (response.hasOwnProperty('config') && response.config.hasOwnProperty('block')) {
                                $rootScope.isBlocked = false;
                            }
                            response.config.responseTimestamp = new Date().getTime();
                            return response;
                        },
                        'responseError': function (response) {
                            return response;
                        }
                    };
                }]);


            /**
             * The workhorse; converts an object to x-www-form-urlencoded serialization.
             * @param {Object} obj
             * @return {String}
             */
            var param = function (obj) {
                var query = '', name, value, fullSubName, subName, subValue, innerObj, i;

                for (name in obj) {
                    value = obj[name];

                    if (value instanceof Array) {
                        for (i = 0; i < value.length; ++i) {
                            subValue = value[i];
                            fullSubName = name + '[' + i + ']';
                            innerObj = {};
                            innerObj[fullSubName] = subValue;
                            query += param(innerObj) + '&';
                        }
                    }
                    else if (value instanceof Object) {
                        for (subName in value) {
                            subValue = value[subName];
                            fullSubName = name + '[' + subName + ']';
                            innerObj = {};
                            innerObj[fullSubName] = subValue;
                            query += param(innerObj) + '&';
                        }
                    }
                    else if (isDefined(value) && value !== null)
                        query += encodeURIComponent(name) + '=' + encodeURIComponent(value) + '&';
                }

                return query.length ? query.substr(0, query.length - 1) : query;
            };

            // Override $http service's default transformRequest
            $httpProvider.defaults.transformRequest = [function (data, getHeaders) {
                    return isObject(data) && String(data) !== '[object File]' ? param(data) : data;
                }];
        }]);

    module.run(['$rootScope', '$state', '$stateParams', function ($rootScope, $state, $stateParams) {
            $rootScope.$state = $state;
            $rootScope.$stateParams = $stateParams;
        }]);

    module.controller(baseController, ['$rootScope', '$scope', '$sce', 'globalConfig', 'menuTree', '$http', 'modalNormal', '$stateParams', '$timeout', 'headTitle', '$state', 'commonService', function ($rootScope, $scope, $sce, globalConfig, menuTree, $http, modalNormal, $stateParams, $timeout, headTitle, $state, commonService) {
            //variables
            mine = $scope;
            $rootScope.configData = globalConfig.rows;
            $rootScope.is_viewer = globalConfig.rows.is_viewer;
            $rootScope.is_master = globalConfig.rows.is_master;
            $rootScope.headTitle = headTitle;
            $rootScope.pageData = {
                'curPage': 1,
                'total': 0,
                'limit': 10
            };  //only do this if $scope.pageData has not already been declared
            $rootScope.searchData = {'data': ''};
            $rootScope.langList = globalConfig.rows.lang;
            $rootScope.curLanguage = copy($stateParams.langCode);
            $rootScope.reqTime = 0;
            //execute splash
            $rootScope.isSplash = true;
            
            $scope.loginUrl = [baseConfig.tplPath, 'sign', 'login.html'].join('/');
            $scope.registerUrl = [baseConfig.tplPath, 'sign', 'register.html'].join('/');
            $scope.menuTree = menuTree.tree;
            $scope.menuArr = menuTree.rows;
            $scope.opinionForm = {};
            $scope.regisForm = {};
            $scope.loginInfo = {};
            $scope.canClick = true;

            //function
            $scope.changeLanguage = function (code) {
                var hash = location.hash.split('/');
                hash[(hash.length - 2)] = code;
                hash[(hash.length - 1)] = '';
                location.href = location.origin + location.pathname + hash.join('/');
                location.reload();
            };
            $rootScope.formatHtml = function (html) {
                return $sce.trustAsHtml(html);
            };
            //head title
            $scope.clickOnHead = function () {
                if ($rootScope.curMenuInfo) {
                    $scope.cancelNewMenu();
                }
            };
            $scope.editHeadTitle = function () {
                $rootScope.headTitle.editting = true;
                $rootScope.headTitle.editname = copy($rootScope.headTitle.name);
            };
            $scope.cancelNewHead = function () {
                $rootScope.headTitle.editting = false;
                $rootScope.headTitle.editname = copy($rootScope.headTitle.name);
            };
            $scope.accessNewHead = function ($event) {
                if ((isDefined($event) && $event.which === 13) || !isDefined($event)) {
                    var headTitle = $rootScope.headTitle;
                    if (headTitle.editname.trim().length > 0) {
                        var path = [baseConfig.apiUrl, 'help', 'help_page', 'change_name'].join('/');
                        $http.post(path, {'id': headTitle.id, 'name': headTitle.editname}).then(function (result) {
                            var data = result.data;
                            if (data.success) {
                                headTitle.name = copy(headTitle.editname);
                                headTitle.subname = headTitle.name.toLowerCase().replace(/([ -.&_])/g, '');
                                headTitle.editting = false;
                                //check: current page is in head page
                                if (!$rootScope.curMenuInfo) {
                                    $state.current.data.pageTitle = headTitle.name;
                                    $state.current.data.specialClass = headTitle.subname;
                                }
                            } else {
                                modalNormal.alert({title: $scope.lang.alert_error_msg, size: 'sm', body: $scope.lang.hp_sth_wrong, hasBtn: false});
                            }
                        });
                    } else {
                        modalNormal.alert({title: $scope.lang.archive_alertobject, size: 'sm', body: $scope.lang.hp_name_no_empty, hasBtn: false});
                    }
                }
            };
            //menu tree
            $scope.addMenu = function (id, hasChild) {
                $scope.addMenuId = id;
                $scope.newMenu = {'parentid': id, 'name': 'New Menu', 'lang': $scope.curLanguage};
                if (hasChild && !$('#menu_' + id).parent().find('ul').hasClass('in')) {
                    $timeout(function () {
                        $('#menu_' + id).click();
                    }, 0);
                }

            };
            $scope.addNewMenu = function ($event) {
                if ((isDefined($event) && $event.which === 13) || !isDefined($event)) {
                    var path = [baseConfig.apiUrl, 'help', 'help_page', 'add_menu'].join('/');
                    var params = copy($scope.newMenu);
                    params.crm = enableCRM;
                    $http.post(path, params).then(function (result) {
                        var data = result.data;
                        if (data.success) {
                            data.menuinfo.nestParent = [];
                            if (data.menuinfo.parentid != '0') {
                                //get parent by cursor
                                cursor = null;
                                getTreeItemById($scope.menuTree, data.menuinfo.parentid);
                                if (cursor) {
                                    data.menuinfo.nestParent = cursor.nestParent;
                                    data.menuinfo.nestParent.push(data.menuinfo.parentid);
                                }
                            }
                            addToMenuTree($scope.menuTree, data.menuinfo);
                        } else {
                            modalNormal.alert({title: $scope.lang.archive_alertobject, size: 'sm', body: data.message, hasBtn: false});
                        }
                    });
                    $scope.cancelNewMenu();
                }
            };
            $scope.cancelNewMenu = function () {
                $scope.addMenuId = '';
            };
            $scope.removeMenu = function (id) {
                window.delMenuId = id;
                modalNormal.alert({title: $scope.lang.alert_warning_msg, size: 'sm', body: $scope.lang.hp_cofirm_delete, okCallback: function () {
                        var path = [baseConfig.apiUrl, 'help', 'help_page', 'remove_menu'].join('/');
                        $http.post(path, {'menuid': window.delMenuId}).then(function (result) {
                            var data = result.data;
                            if (data.success) {
                                var hash = location.hash.split('/');
                                hash[(hash.length - 1)] = '';
                                removeFrMenuTree($scope.menuTree, window.delMenuId);
                            }
                        });
                    }});
            };
            $scope.editMenu = function (menu) {
                menu.editting = true;
                menu.editname = copy(menu.name);
            };
            $scope.cancelNewName = function (menu) {
                menu.editname = copy(menu.name);
                menu.editting = false;
            };
            $scope.accessNewName = function (menu, $event) {
                if ((isDefined($event) && $event.which === 13) || !isDefined($event)) {
                    if (menu.editname.trim().length > 0) {
                        if (menu.name != menu.editname) {
                            var path = [baseConfig.apiUrl, 'help', 'help_page', 'change_name'].join('/');
                            $http.post(path, {'id': menu.id, 'name': menu.editname}).then(function (result) {
                                var data = result.data;
                                if (data.success) {
                                    menu.name = copy(menu.editname);
                                    menu.subname = menu.name.toLowerCase().replace(/([ -.&_])/g, '');
                                    menu.editting = false;
                                    //check: current page is in this menu
                                    if (!isDefined(menu.children) && $rootScope.curMenuInfo.id == menu.id) {
                                        $state.current.data.pageTitle = menu.name;
                                        $state.current.data.specialClass = menu.subname;
                                    }
                                } else {
                                    modalNormal.alert({title: $scope.lang.alert_error_msg, size: 'sm', body: $scope.lang.hp_sth_wrong, hasBtn: false});
                                }
                            });
                        } else {
                            menu.editting = false;
                        }
                    } else {
                        modalNormal.alert({title: $scope.lang.archive_alertobject, size: 'sm', body: $scope.lang.hp_name_no_empty, hasBtn: false});
                    }
                }
            };
            $scope.clickOnTab = function (menu) {
                if ($scope.addMenuId !== menu.id)
                    $scope.cancelNewMenu();
            };
            //search bar
            $scope.enterSearch = function () {
                //reset pageData
                $rootScope.pageData = {
                    'curPage': 1,
                    'total': 0,
                    'limit': 10,
                    'result': []
                };
                $scope.search(true);
            };
            $scope.search = function (enter) {
                //check: if entering, change keyword 
                if (enter)
                    $scope.pageData.search = copy($rootScope.searchData.data);
                //validate search data
                if ($scope.pageData.search.indexOf("'") == -1) {
                    if ($scope.pageData.search.trim().length > 0) {
                        $state.go('help.menu', {location: 'replace', langCode: $rootScope.curLanguage, menuId: 'search'});
                        var params = {
                            'lang': $rootScope.curLanguage,
                            'keyword': $scope.pageData.search,
                            'curPage': $scope.pageData.curPage,
                            'total': $scope.pageData.total,
                            'limit': $scope.pageData.limit,
                            'crm': enableCRM
                        };
                        var path = [baseConfig.apiUrl, 'help', 'help_page', 'search_data'].join('/');
                        $http.post(path, params, {'block': enter}).then(function (response) {
                            $rootScope.reqTime = response.config.responseTimestamp - response.config.requestTimestamp;
                            var data = response.data;
                            if (data.success) {
                                $rootScope.pageData.result = data.rows;
                                $rootScope.pageData.total = data.total;
                                //config data response
                                forEach($rootScope.pageData.result, function (val, k) {
                                    var menu = getItemById($scope.menuArr, val.id);
                                    val.direct = copy(menu.direct);
                                });
                            } else {
                                modalNormal.alert({title: $scope.lang.archive_alertobject, size: 'sm', body: $scope.lang.hp_sth_wrong, hasBtn: false});
                            }
                        });
                    }
                } else {
                    modalNormal.alert({title: $scope.lang.archive_alertobject, size: 'sm', body: "Keyword is not empty and does not include character \" ' \"", hasBtn: false});
                }
            };
            $scope.$watch('pageData.curPage', function (newValue, oldValue) {
                if (($rootScope.pageData.total / $rootScope.pageData.limit) > 1)
                    $scope.search(false);
            });
            //navigation
            $scope.sortableOptions = {
                disabled: $scope.is_viewer,
                start: function (e, ui) {
                    movingMenu = true;
                },
                update: function (e, ui) {
                    var item = ui.item.scope().item;
                    var menuOrder = [];
                    if (item.parentid == '0') {
                        $timeout(function () {
                            var menuIds = $scope.menuTree.map(function (i) {
                                return i.id;
                            });
                            forEach(menuIds, function (val, k) {
                                menuOrder.push({'id': val, 'order': k + 1});
                            });
                            commonService.orderMenu({'menuorder': menuOrder});
                        }, 0);
                    } else {
                        $timeout(function () {
                            cursor = null;
                            getTreeItemById($scope.menuTree, item.parentid);
                            if (cursor && isDefined(cursor.children)) {
                                var menuIds = cursor.children.map(function (i) {
                                    return i.id;
                                });
                                forEach(menuIds, function (val, k) {
                                    menuOrder.push({'id': val, 'order': k + 1});
                                });
                                commonService.orderMenu({'menuorder': menuOrder});
                            }
                        }, 0);
                    }
                },
                axis: 'y'
            };
            //header
            //function for export pdf 
            $scope.exportAllPdf = function () {
                if ($scope.canClick) {
                    $scope.canClick = false;
                    var path = [baseConfig.apiUrl, 'help', 'help_page', 'export_all_pdf'].join('/');
                    //change url of images
                    var path_name = window.location.pathname.split('/')[1];
                    var url = window.location.origin + "/" + path_name + "/help/images";
                    var params = {
                        'isAll': true,
                        'lang': $rootScope.curLanguage,
                        'url': url
                    };
                    $http.post(path, params).then(function (result) {
                        var file_name = result.data.file_name;
                        var path_name = window.location.pathname.split('/')[1];
                        var url = window.location.origin + "/" + path_name + "/help/pdf?file=" + file_name + '&filename=Helppage_GW';
                        //window.open(url);
                        $scope.canClick = true;
                    });
                }
            };
            $scope.register = function () {
                commonService.register($scope.regisForm).then(function(result) {
                    if (result.success) {
                        $scope.regisForm = {};
                        modalNormal.confirm({title: $scope.lang.alert_success_msg, size: 'sm', body: result.message, type: 'success', hasBtn: false});
                    } else {
                        modalNormal.alert({title: $scope.lang.alert_error_msg, size: 'sm', body: result.message, hasBtn: false});
                    }
                });
            };
            $scope.login = function () {
                commonService.login($scope.loginInfo).then(function(result) {
                    if (result.success) {
                        $rootScope.isSplash = true;
                        $scope.loginInfo = {};
                        $state.go('help.menu', {location: 'replace', langCode: $rootScope.curLanguage, menuId: ''}, {reload: true});
                    } else {
                        modalNormal.alert({title: $scope.lang.alert_error_msg, size: 'sm', body: result.message, hasBtn: false});
                    }
                });
            };
            $scope.logout = function () {
                commonService.logout().then(function(result) {
                    if (result.success) {
                        $rootScope.isSplash = true;
                        $state.go('help.menu', {location: 'replace', langCode: $rootScope.curLanguage, menuId: ''}, {reload: true});
                    }
                });
            };
            $scope.splash = function () {
                $rootScope.isSplash = true;
            };

            //support function
            function addToMenuTree(tree, menuInfo) {
                if (isDefined(menuInfo)) {
                    if (menuInfo.parentid != '0') {
                        forEach(tree, function (val, key) {
                            if (val.id == menuInfo.parentid) {
                                if (!isDefined(val.children)) {
                                    val.children = [];
                                    val.children.push(menuInfo);
                                } else {
                                    val.children.push(menuInfo);
                                }
                            } else if (isDefined(val.children)) {
                                addToMenuTree(val.children, menuInfo);
                            }
                        });
                    } else {
                        tree.push(menuInfo);
                    }
                }
            }
            function removeFrMenuTree(tree, menuId, parent) {
                if (isDefined(menuId)) {
                    forEach(tree, function (val, key) {
                        if (val.id == menuId) {
                            if (val.parentid == '0') {
                                tree.splice(key, 1);
                            } else {
                                if (parent.children.length === 1) {
                                    delete parent.children;
                                } else {
                                    parent.children.splice(key, 1);
                                }
                            }
                        } else if (isDefined(val.children)) {
                            removeFrMenuTree(val.children, menuId, val);
                        }
                    });
                }
            }
            $scope.checkRelation = function (nodeId1, nodeId2) {
                if (!isNaN(parseInt(nodeId1)) && !isNaN(parseInt(nodeId2))) {
                    getTreeItemById($scope.menuTree, nodeId1);
                    var node1 = copy(cursor);
                    getTreeItemById($scope.menuTree, nodeId2);
                    var node2 = copy(cursor);
                    if (node1 && node2 && (node1.nestParent.indexOf(nodeId2) != -1 || node2.nestParent.indexOf(nodeId1) != -1)) {
                        return true;
                    } else {
                        return false;
                    }
                }
            };

        }]);

    /* Factory */
    module.factory('langService', ["$http", "$q", "$rootScope", "$stateParams", function ($http, $q, $rootScope, $stateParams) {
            var lang = {};
            if (!isObject($rootScope['lang'])) {
                $rootScope.lang = {};
            }

            var langPromise = function (category) {
                var deffered = $q.defer();
                var params = {};
                var url = location.hash.split('/');
                if (url.length > 3) {
                    params.lang = url[2];
                }
                var langApi = [baseConfig.langApi, category].join('/');
                $http.post(langApi, params).success(function (res) {
                    $rootScope.lang = extend($rootScope.lang, res.rows);
                    lang[category] = res.rows;
                    deffered.resolve(res.rows);
                });

                return deffered.promise;
            };

            return {
                reload: langPromise,
                get: function (category) {
                    // if the front and the rear login language isn't same, get new
                    if (($rootScope.loginLang == $rootScope.nowLang) && lang.hasOwnProperty(category)) {
                        return lang[category];
                    } else {
                        return langPromise(category);
                    }
                },
                getAll: function () {
                    return lang;
                }
            }
        }]);
    module.factory('commonService', ['$http', '$q', function ($http, $q) {

            this.getGlobalConfig = function () {
                var defer = $q.defer();
                var path = [baseConfig.apiUrl, 'help', 'help_page', 'config'].join('/');
                $http.get(path)
                        .success(function (res) {
                            defer.resolve(res);
                        })
                        .error(function (res) {
                            defer.resolve(res);
                        })

                return defer.promise;
            };

            this.getUserConfig = function () {
                var defer = $q.defer();
                $http.get(baseConfig.apiUrl + '/main/config/mode/user')
                        .success(function (res) {
                            defer.resolve(res);
                        })
                        .error(function (res) {
                            defer.resolve(res);
                        })
                return defer.promise;
            };
            
            this.register = function (params) {
                var defer = $q.defer();
                var path = [baseConfig.apiUrl, 'help', 'help_page', 'register_user'].join('/');
                $http.post(path, params)
                        .success(function (res) {
                            defer.resolve(res);
                        })
                        .error(function (res) {
                            defer.resolve(res);
                        });
                return defer.promise;
            };
            
            this.login = function (params) {
                var defer = $q.defer();
                var path = [baseConfig.apiUrl, 'help', 'help_page', 'login_user'].join('/');
                $http.post(path, params)
                    .success(function (res) {
                        defer.resolve(res);
                    })
                    .error(function (res) {
                        defer.resolve(res);
                    });
                return defer.promise;
            };
            
            this.logout = function () {
                var defer = $q.defer();
                var path = [baseConfig.apiUrl, 'help', 'help_page', 'logout_user'].join('/');
                $http.get(path)
                    .success(function (res) {
                        defer.resolve(res);
                    })
                    .error(function (res) {
                        defer.resolve(res);
                    });
                return defer.promise;
            };

            this.orderMenu = function (params) {
                var path = [baseConfig.apiUrl, 'help', 'help_page', 'order_menu'].join('/');
                var defer = $q.defer();
                $http.post(path, params)
                        .success(function (res) {
                            defer.resolve(res);
                        })
                        .error(function (res) {
                            defer.resolve(res);
                        });

                return defer.promise;
            };

            return this;
        }]);
    module.factory('treeService', ['$http', '$q', '$stateParams', function ($http, $q, $stateParams) {

            this.getTree = function () {
                var defer = $q.defer();

                var path = [baseConfig.apiUrl, 'help', 'help_page', 'menu_tree'].join('/');
                var hash = location.hash.split('/');
                var params = {
                    'lang': hash[(hash.length - 2)],
                    'crm': enableCRM
                };
                $http.post(path, params)
                        .success(function (res) {
                            defer.resolve(res);
                        })
                        .error(function (res) {
                            defer.resolve(res);
                        });

                return defer.promise;
            };

            this.getTitle = function () {
                var defer = $q.defer();

                var path = [baseConfig.apiUrl, 'help', 'help_page', 'menu_title'].join('/');
                var hash = location.hash.split('/');
                var params = {
                    'lang': hash[(hash.length - 2)],
                    'crm': enableCRM
                };
                $http.post(path, params)
                        .success(function (res) {
                            defer.resolve(res);
                        })
                        .error(function (res) {
                            defer.resolve(res);
                        });

                return defer.promise;
            };
            return this;
        }]);
    module.factory("modalNormal", ['$http', '$modal', '$rootScope', function ($http, $modal, $rootScope) {
            var lang = $rootScope.lang;

            var openModal = function (config) {
                $modal.open(config);
            };

            var getController = function (options, type) {
                return ['$scope', '$modalInstance', function ($scope, $modalInstance) {
                        $scope.title = (options.title) ? options.title : '';
                        $scope.subTitle = (options.subTitle) ? options.subTitle : '';
                        $scope.body = (options.body) ? options.body : '';
                        $scope.hasBtn = (isDefined(options.hasBtn)) ? options.hasBtn : true;

                        $scope.showOKBtn = true;
                        if (type === 'confirm') {
                            $scope.showOKBtn = false;
                        }

                        $scope.ok = function () {
                            $modalInstance.close();
                            if (options.hasOwnProperty('okCallback') && isFunction(options.okCallback)) {
                                options.okCallback($scope, $http);
                            }
                        };

                        $scope.cancel = function () {
                            $modalInstance.dismiss('cancel');
                            if (options.hasOwnProperty('cancelCallback') && isFunction(options.cancelCallback)) {
                                options.cancelCallback($scope, $http);
                            }
                        };
                    }];
            };

            return {
                alert: function (options) {
                    var config = {
                        templateUrl: 'template/modal/modal_normal.html',
                        windowClass: 'hmodal-danger'
                    };
                    //small: sm; large: lg; default is medium
                    if (options.size)
                        config.size = options.size;
                    //check and add template
                    if (options.templateUrl)
                        config.templateUrl = options.templateUrl;

                    config.controller = getController(options);
                    return openModal(config);
                },
                confirm: function (options) {
                    var windowClass = 'hmodal-warning';
                    if (options.type) {
                        switch (options.type) {
                            case 'info':
                                windowClass = 'hmodal-info';
                                break;
                            case 'success':
                                windowClass = 'hmodal-success';
                                break;
                        }
                    }

                    var config = {
                        templateUrl: 'template/modal/modal_normal.html',
                        windowClass: windowClass
                    };
                    //small: sm; large: lg; default is medium
                    if (options.size)
                        config.size = options.size;
                    //check and add template
                    if (options.templateUrl)
                        config.templateUrl = options.templateUrl;

                    config.controller = getController(options, 'confirm');
                    return openModal(config);
                },
                warning: function (options) {
                    var windowClass = 'hmodal-warning';
                    var config = {
                        templateUrl: 'template/modal/modal_normal.html',
                        windowClass: windowClass
                    };
                    //small: sm; large: lg; default is medium
                    if (options.size)
                        config.size = options.size;
                    //check and add template
                    if (options.templateUrl)
                        config.templateUrl = options.templateUrl;

                    config.controller = getController(options, 'warning');
                    return openModal(config);
                }
            };
        }]);

    /* Directive */
    module.directive('minimalizaMenu', ['$rootScope', function ($rootScope) {
            return {
                restrict: 'EA',
                template: '<div class="header-link hide-menu" ng-click="minimalize()"><i class="fa fa-bars"></i></div>',
                controller: function ($scope, $element) {
                    $scope.minimalize = function () {
                        if ($(window).width() < 769) {
                            $("body").toggleClass("show-sidebar");
                        } else {
                            $("body").toggleClass("hide-sidebar");
                        }
                    }
                }
            };
        }]);
    module.directive('sideNavigation', ['$timeout', function ($timeout) {
            return {
                restrict: 'A',
                link: function (scope, element) {
                    // Call the metsiMenu plugin and plug it to sidebar navigation
                    var firsttime = false;
                    setTimeout(function () {
                        element.metisMenu({toggle: true});
                        // Colapse menu in mobile mode after click on element
                        var menuElement = $('#side-menu a:not([href$="\\#"])');
                        menuElement.click(function () {

                            if ($(window).width() < 769) {
                                $("body").toggleClass("show-sidebar");
                            }
                        });
                        firsttime = true;
                    }, 10);

                    scope.$watch('menuTree', function () {
                        setTimeout(function () {
                            if (!firsttime && !movingMenu) {
                                element.metisMenu({toggle: false});
                                // Colapse menu in mobile mode after click on element
                                var menuElement = $('#side-menu a:not([href$="\\#"])');
                                menuElement.click(function () {

                                    if ($(window).width() < 769) {
                                        $("body").toggleClass("show-sidebar");
                                    }
                                });
                            } else {
                                firsttime = false;
                                movingMenu = false;
                            }
                        }, 200);
                    }, true);
                }
            };
        }]);
    module.directive('smallHeader', [function () {
            return {
                restrict: 'A',
                scope: true,
                controller: function ($scope, $element) {
                    $scope.small = function () {
                        var icon = $element.find('i:first');
                        var breadcrumb = $element.find('#hbreadcrumb');
                        $element.toggleClass('small-header');
                        breadcrumb.toggleClass('m-t-lg');
                        icon.toggleClass('fa-arrow-up').toggleClass('fa-arrow-down');
                    }
                }
            }
        }]);
    module.directive('sparkline', [function () {
            return {
                restrict: 'A',
                scope: {
                    sparkData: '=',
                    sparkOptions: '=',
                },
                link: function (scope, element, attrs) {
                    scope.$watch(scope.sparkData, function () {
                        render();
                    });
                    scope.$watch(scope.sparkOptions, function () {
                        render();
                    });
                    var render = function () {
                        $(element).sparkline(scope.sparkData, scope.sparkOptions);
                    };
                }
            }
        }]);
    module.directive('summerNote', ['$timeout', '$http', '$rootScope', function ($timeout, $http, $rootScope) {
            return {
                restrict: 'E',
                scope: true,
                templateUrl: 'template/panel/panel.html',
                controller: function ($scope, $element, $attrs) {
                    $timeout(function () {
                        if (isDefined($attrs['menuId']) && $attrs['menuId'] != '') {
                            var path = [baseConfig.apiUrl, 'help', 'help_page', 'list_contents'].join('/');
                            $http.post(path, {'menuid': $attrs['menuId'], 'lang': $scope.curLanguage}, httpBlockConfig).then(function (result) {
                                var data = result.data;
                                if (data.success)
                                    $rootScope.list = data.row;
                                else
                                    $rootScope.list = [];
                            });
                        }
                    }, 0);
                }
            };
        }]);
    module.directive('panelTools', ['$timeout', '$http', 'modalNormal', '$rootScope', function ($timeout, $http, modalNormal, $rootScope) {
            return {
                restrict: 'A',
                scope: true,
                //templateUrl: 'views/common/panel_tools.html',
                controller: function ($scope, $element, $attrs, $stateParams, $rootScope) {
                    $timeout(function () {
                        $scope.canClick = true;
                        // Function for collapse ibox
                        $rootScope.showhide = function ($event) {
                            var selector = $event.target.closest('div.panel-heading').children[0];
                            var element = ngElement(selector);
                            var hpanel = element.closest('div.hpanel');
                            var icon = element.find('i:first');
                            var body = hpanel.find('div.panel-body');
                            var footer = hpanel.find('div.panel-footer');
                            body.slideToggle(300);
                            footer.slideToggle(200);
                            // Toggle icon from up to down
                            icon.toggleClass('fa-chevron-up').toggleClass('fa-chevron-down');
                            hpanel.toggleClass('').toggleClass('panel-collapse');
                            $timeout(function () {
                                hpanel.resize();
                                hpanel.find('[id^=map-]').resize();
                            }, 50);
                        };
                        // Function for close ibox
                        $scope.closebox = function () {
                            var hpanel = $element.closest('div.hpanel');
                            hpanel.remove();
                        };
                        $scope.isEdit = false;
                        //Function for edit ibox
                        $scope.edit = function (item) {
                            $scope.isEdit = true;
                            $('#content_' + item.id).summernote({
                                codemirror: {// codemirror options
                                    theme: 'monokai'
                                }
                            });
                            if ($('#content_' + item.id).parent().parent().hasClass('panel-collapse')) {
                                $('#content_' + item.id).parent().parent().removeClass('panel-collapse');
                                $('#content_' + item.id).parent().css('display', 'block');
                            }
                            //remove padding of panel-body
                            $('#content_' + item.id).parent().css('padding', '0px');
                        };
                        //Function for save ibox
                        $scope.save = function (item) {
                            var params = {
                                'content': $('#content_' + item.id).code(),
                                'id': item.id
                            };

                            var path = [baseConfig.apiUrl, 'help', 'help_page', 'edit_content'].join('/');
                            $http.post(path, params).then(function (result) {
                                var data = result.data;
                                if (data.success) {
                                    $scope.isEdit = false;
                                    item.content = $('#content_' + item.id).code();
                                    $('#content_' + item.id).destroy();
                                    //add padding of panel-body
                                    $('#content_' + item.id).parent().css('padding', '');
                                } else {
                                    modalNormal.alert({title: $scope.lang.alert_error_msg, size: 'sm', body: $scope.lang.hp_sth_wrong, hasBtn: false});
                                }
                            });
                        };
                        //Function for cancel ibox
                        $scope.cancel = function (item) {
                            if (item) {
                                if ($('#content_' + item.id).code() != item.content) {
                                    modalNormal.warning({title: $scope.lang.alert_warning_msg, size: 'sm', templateUrl: 'template/modal/modal_yesno.html', body: $scope.lang.hp_want_save_doc,
                                        okCallback: function () {
                                            $scope.save(item);
                                            item.content = $('#content_' + item.id).code();
                                        }, cancelCallback: function () {
                                            $scope.isEdit = false;
                                            $('#content_' + item.id).destroy();
                                            $('#content_' + item.id).html(item.content);
                                            //add padding of panel-body
                                            $('#content_' + item.id).parent().css('padding', '');
                                        }});
                                } else {
                                    $scope.isEdit = false;
                                    $('#content_' + item.id).destroy();
                                    $('#content_' + item.id).html(item.content);
                                    //add padding of panel-body
                                    $('#content_' + item.id).parent().css('padding', '');
                                }
                            }
                        };
                        //Function for remove ibox
                        $scope.remove = function (item) {
                            modalNormal.alert({title: $scope.lang.alert_warning_msg, size: 'sm', body: $scope.lang.hp_cofirm_delete, okCallback: function () {
                                    var path = [baseConfig.apiUrl, 'help', 'help_page', 'remove_article'].join('/');
                                    $http.post(path, {'id': item.id}).then(function (result) {
                                        var data = result.data;
                                        if (data.success) {
                                            $scope.closebox();
                                        } else {
                                            modalNormal.alert({title: $scope.lang.alert_error_msg, size: 'sm', body: $scope.lang.hp_sth_wrong, hasBtn: false});
                                        }
                                    });
                                }});
                        };
                        //function for export pdf 
                        $scope.exportPdf = function (item) {
                            if ($scope.canClick) {
                                $scope.canClick = false;
                                var path = [baseConfig.apiUrl, 'help', 'help_page', 'export_pdf'].join('/');
                                var params = $('#content_' + item.id).parent().parent()[0].outerHTML;
                                //change url of images
                                var path_name = window.location.pathname.split('/');
                                path_name.splice(path_name.length - 2, 2);
                                path_name = path_name.join('/');
                                var url = window.location.origin + path_name + "/help/images";

                                var content = params.replace(/(".\/images)|("images)/g, '"' + url);
                                $http.post(path, {'content': content}).then(function (result) {
                                    $scope.canClick = true;
                                    var file_name = result.data.file_name;
                                    var url = baseConfig.apiUrl + "/help/pdf?file=" + file_name + '&filename=' + item.title;
                                    window.open(url);
                                });
                            }
                        };
                    }, 0);
                }
            };
        }]);
    module.directive('animatePanel', ['$timeout', '$state', function ($timeout, $state) {
            return {
                restrict: 'A',
                link: function (scope, element, attrs) {

                    //Set defaul values for start animation and delay
                    var startAnimation = 0;
                    var delay = 0.06;   // secunds
                    var start = Math.abs(delay) + startAnimation;

                    // Store current state where directive was start
                    var currentState = $state.current.name;

                    // Set default values for attrs
                    if (!attrs.effect) {
                        attrs.effect = 'zoomIn'
                    }
                    ;
                    if (attrs.delay) {
                        delay = attrs.delay / 10
                    } else {
                        delay = 0.06
                    }
                    ;
                    if (!attrs.child) {
                        attrs.child = '.row > div'
                    } else {
                        attrs.child = "." + attrs.child
                    }
                    ;

                    // Get all visible element and set opactiy to 0
                    var panel = element.find(attrs.child);
                    panel.addClass('opacity-0');

                    // Count render time
                    var renderTime = panel.length * delay * 1000 + 700;

                    // Wrap to $timeout to execute after ng-repeat
                    $timeout(function () {

                        // Get all elements and add effect class
                        panel = element.find(attrs.child);
                        panel.addClass('animated-panel').addClass(attrs.effect);

                        // Add delay for each child elements
                        panel.each(function (i, elm) {
                            start += delay;
                            var rounded = Math.round(start * 10) / 10;
                            $(elm).css('animation-delay', rounded + 's')
                            // Remove opacity 0 after finish
                            $(elm).removeClass('opacity-0');
                        });

                        // Clear animate class after finish render
                        $timeout(function () {

                            // Check if user change state and only run renderTime on current state
                            if (currentState == $state.current.name) {
                                // Remove effect class - fix for any backdrop plgins (e.g. Tour)
                                $('.animated-panel:not([ng-repeat]').removeClass(attrs.effect);
                            }
                        }, renderTime)

                    });

                }
            }
        }]);
    module.directive('icheck', ['$timeout', function ($timeout) {
            return {
                restrict: 'A',
                require: 'ngModel',
                link: function ($scope, element, $attrs, ngModel) {
                    return $timeout(function () {
                        var value;
                        value = $attrs['value'];

                        $scope.$watch($attrs['ngModel'], function (newValue) {
                            $(element).iCheck('update');
                        })

                        return $(element).iCheck({
                            checkboxClass: 'icheckbox_square-green',
                            radioClass: 'iradio_square-green'

                        }).on('ifChanged', function (event) {
                            if ($(element).attr('type') === 'checkbox' && $attrs['ngModel']) {
                                $scope.$apply(function () {
                                    return ngModel.$setViewValue(event.target.checked);
                                });
                            }
                            if ($(element).attr('type') === 'radio' && $attrs['ngModel']) {
                                return $scope.$apply(function () {
                                    return ngModel.$setViewValue(value);
                                });
                            }
                        });
                    });
                }
            };
        }]);
    module.directive('touchSpin', [function () {
            return {
                restrict: 'A',
                scope: {
                    spinOptions: '=',
                },
                link: function (scope, element, attrs) {
                    scope.$watch(scope.spinOptions, function () {
                        render();
                    });
                    var render = function () {
                        $(element).TouchSpin(scope.spinOptions);
                    };
                }
            }
        }]);
    module.directive('landingScrollspy', [function () {
            return {
                restrict: 'A',
                link: function (scope, element, attrs) {
                    element.scrollspy({
                        target: '.navbar-fixed-top',
                        offset: 80
                    });
                }
            }
        }]);
    module.directive('stopEvent', function () {
        return {
            restrict: 'A',
            link: function (scope, element, attr) {
                element.bind('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                });
            }
        };
    });
    module.directive('ngEnter', function () {
        return function (scope, element, attrs) {
            element.bind("keydown keypress", function (event) {
                if (event.which === 13) {
                    scope.$apply(function () {
                        scope.$eval(attrs.ngEnter);
                    });

                    event.preventDefault();
                }
            });
        };
    });
})();

