(function () {
    'use strict';

    /******************* Define Modules *******************/
    //Module config
    //Mail
    var moduleConfig = baseConfig.getModuleConfig('menu');
    var module = angular.module(moduleConfig.moduleName, []);
    module.config(['$stateProvider', '$urlRouterProvider', function ($stateProvider, $urlRouterProvider) {
            $urlRouterProvider.otherwise('menu/en/');
            //parent state
            $stateProvider.state('help.menu', {
                url: "/:menuId",
                templateUrl: "template/common/main.html",
                data: {
                    pageTitle: '',
                    specialClass: ''
                },
                controller: 'menuCtrl'
            });
        }]);

    module.controller('menuCtrl', ['$rootScope', '$scope', '$state', '$stateParams', 'menuService', 'menuTree', 'modalNormal', '$http', function ($rootScope, $scope, $state, $stateParams, menuService, menuTree, modalNormal, $http) {
            var curMenuId = $stateParams.menuId;
            var lang = $rootScope.lang;
            $scope.exceptIds = [$rootScope.headTitle.id, 'feedback', 'search', 'login', '', 'users'];
            $scope.exceptFooter = ['', 'feedback', 'search', 'users'];
            $scope.feedbackUrl = [baseConfig.tplPath, 'common', 'feedback.html'].join('/');
            $scope.searchUrl = [baseConfig.tplPath, 'common', 'search.html'].join('/');
            $scope.usersUrl = [baseConfig.tplPath, 'common', 'users.html'].join('/');
            $scope.pagination = {
                'curPage': 1,
                'total': 0,
                'limit': 10
            };  //only do this if $scope.pageData has not already been declared
            //execute splash
            $rootScope.isSplash = false;
            if (document.getElementById('splash-screen').getAttribute('style')) {
                document.getElementById('splash-screen').removeAttribute('style');
            }
            
            //reset variable
            $scope.opinionForm = {};
            $rootScope.feedbackItems = [];
            $rootScope.pagination = {
                'limit': 10,
                'curPage': 0,
                'maxPage': 0
            };
            $rootScope.list = [];
            
            //support tools
            var getCurMenu = function (tree, value) {
                var result = {};
                forEach(tree, function (val, key) {
                    if (val.id == value) {
                        $rootScope.curMenuInfo = val;
                    } else if (isDefined(val.children)) {
                        getCurMenu(val.children, value);
                    }
                });
                return result;
            };
            $scope.getDate = function (date, format) {
                return moment(date).format(format);
            };
            
            //function
            $scope.sendOpinionMsg = function () {
                var params = $scope.opinionForm;
                params.menuid = curMenuId;
                params.crm = enableCRM;
                menuService.sendMsg(params).then(function (result) {
                    var data = result.data;
                    if (data.success)
                        modalNormal.confirm({title: lang.alert_success_msg, size: 'sm', body: lang.alert_apply_fee_success, type: 'success', hasBtn: false});
                    else
                        modalNormal.alert({title: lang.alert_error_msg, size: 'sm', body: lang.hp_sth_wrong, hasBtn: false});
                });
                $scope.opinionForm = {};
            };
            $scope.addArticle = function () {
                modalNormal.confirm({title: lang.hp_new_articles, templateUrl: 'template/common/add_article.html', type: 'info', okCallback: function ($scope, $http) {
                    if (curMenuId == $scope.headTitle.id) {
                        var params = {
                            'title': $scope.articleTitle, 
                            'menuid': $scope.headTitle.id, 
                            'lang': $scope.curLanguage
                        };
                    } else {
                        var params = {
                            'title': $scope.articleTitle, 
                            'menuid': $scope.curMenuInfo.id, 
                            'lang': $scope.curLanguage
                        };
                    }
                    params.crm = enableCRM;
                    var path = [baseConfig.apiUrl, 'help', 'help_page', 'add_article'].join('/');
                    $http.post(path, params).then(function (result) {
                        var data = result.data;
                        if (data.success) {
                            $scope.list.push(data.data);
                        } else {
                            modalNormal.alert({title: lang.alert_error_msg, size: 'sm', body: lang.hp_sth_wrong, hasBtn: false});
                        }
                    });
                }});
            };
            $rootScope.loadFeedback = function () {
                if ($rootScope.feedbackItems.length !== 0) {
                    $rootScope.pagination.limit = 5;
                }
                $rootScope.pagination.curPage++;
                var params = {
                    'lang': $scope.curLanguage, 
                    'pagination': $rootScope.pagination, 
                    'crm': enableCRM
                };
                menuService.getOpinion(params).then(function(result) {
                    if (result.success) {
                        forEach (result.rows, function(val, k) {
                            val.date = $scope.getDate(val.regdate, 'MMM D, YYYY h:mm:ss A');
                            val.dayOfWeek = $scope.getDate(val.regdate, 'dddd');
                            $rootScope.feedbackItems.push(val);
                            $rootScope.pagination.maxPage = result.maxPage;
                        });
                    } else {
                        modalNormal.alert({title: lang.alert_error_msg, size: 'sm', body: lang.hp_sth_wrong, hasBtn: false});
                    }
                });
            };
            $rootScope.loadUsers = function () {
                var params = {
                    'curPage': $scope.pagination.curPage,
                    'limit': $scope.pagination.limit
                };
                menuService.getUserList(params).then(function(result) {
                    if (result.success) {
                        $scope.userList = result.rows;
                        $scope.pagination.total = result.total;
                    } else {
                        modalNormal.alert({title: lang.alert_error_msg, size: 'sm', body: lang.hp_sth_wrong, hasBtn: false});
                    }
                });
            };
            $scope.editArtTitle = function (item) {
                item.editting = true;
                item.edittitle = copy(item.title);
            };
            $scope.accessArtTitle = function (item, $event) {
                if ((isDefined($event) && $event.which === 13) || !isDefined($event)) {
                    if (item.edittitle.trim().length > 0) {
                        var path = [baseConfig.apiUrl, 'help', 'help_page', 'change_title_name'].join('/');
                        $http.post(path, {'id': item.id, 'title': item.edittitle}).then(function (result) {
                            var data = result.data;
                            if (data.success) {
                                item.title = copy(item.edittitle);
                                item.editting = false;
                            } else {
                                modalNormal.alert({title: lang.alert_error_msg, size: 'sm', body: lang.hp_sth_wrong, hasBtn: false});
                            }
                        });
                    } else {
                        modalNormal.alert({title: lang.archive_alertobject, size: 'sm', body: lang.hp_name_no_empty, hasBtn: false});
                    }
                }
            };
            $scope.cancelArtTitle = function (item) {
                item.editting = false;
                item.edittitle = copy(item.title);
            };
            $scope.activeUser = function (item) {
                menuService.activeUser({'id': item.id}).then(function(result) {
                    if (result.success)
                        item.active = 'Yes';
                    else
                        modalNormal.alert({title: lang.alert_error_msg, size: 'sm', body: lang.hp_sth_wrong, hasBtn: false});
                });
            };
            $scope.disactiveUser = function (item) {
                menuService.disactiveUser({'id': item.id}).then(function(result) {
                    if (result.success)
                        item.active = 'No';
                    else
                        modalNormal.alert({title: lang.alert_error_msg, size: 'sm', body: lang.hp_sth_wrong, hasBtn: false});
                });
            };
            $scope.deleteUser = function (item) {
                modalNormal.alert({title: lang.alert_warning_msg, size: 'sm', body: lang.hp_cofirm_delete, okCallback: function ($scope, $http) {
                    var path = [baseConfig.apiUrl, 'help', 'help_page', 'delete_user'].join('/');
                    $http.post(path, {'id': item.id}, httpBlockConfig).success(function(res) {
                        if (res.success) {
                            $scope.loadUsers();
                        } else {
                            modalNormal.alert({title: lang.alert_error_msg, size: 'sm', body: lang.hp_sth_wrong, hasBtn: false});
                        }
                    });
                }});
                
            };
            //paging user list
            $scope.$watch('pagination.curPage', function (newValue, oldValue) {
                if (($scope.pagination.total / $scope.pagination.limit) > 1) {
                    $scope.loadUsers();
                }
            });
            
            //check: where do user access ?
            switch (curMenuId) {
                case $rootScope.headTitle.id:
                    $rootScope.curMenuInfo = null;
                    $state.current.data.pageTitle = $rootScope.headTitle.name;
                    $state.current.data.specialClass = $rootScope.headTitle.subname;
                    break;
                case 'feedback':
                    $rootScope.curMenuInfo = null;
                    $state.current.data.pageTitle = 'Feedback';
                    $state.current.data.specialClass = 'feedback';
                    $rootScope.loadFeedback();
                    break;
                case '':
                case 'login':
                case 'register':
                case 'search':
                    $rootScope.curMenuInfo = null;
                    break;
                case 'users':
                    $rootScope.curMenuInfo = null;
                    $state.current.data.pageTitle = 'Users';
                    $state.current.data.specialClass = 'users';
                    $scope.loadUsers();
                    break;
                default:
                    //check and get current menu
                    getCurMenu(menuTree.tree, curMenuId);
                    //set pageTitle and specialClass
                    if (isDefined($rootScope.curMenuInfo)) {
                        $state.current.data.pageTitle = $scope.curMenuInfo.name;
                        $state.current.data.specialClass = $scope.curMenuInfo.subname;
                    }
                    break;
            }
        }]);

    module.factory('menuService', ['$http', '$q', function ($http, $q) {
            var service = {};
            
            service.sendMsg = function (params) {
                var path = [baseConfig.apiUrl, 'help', 'help_page', 'send_msg'].join('/');
                return $http.post(path, params, httpBlockConfig);
            };
            service.getOpinion = function (params) {
                var defered = $q.defer();
                var path = [baseConfig.apiUrl, 'help', 'help_page', 'get_opinion'].join('/');
                $http.post(path, params, httpBlockConfig).success(function(res) {
                    defered.resolve(res);
                });
                return defered.promise;
            };
            service.getUserList = function (params) {
                var defered = $q.defer();
                var path = [baseConfig.apiUrl, 'help', 'help_page', 'user_list'].join('/');
                $http.post(path, params, httpBlockConfig).success(function(res) {
                    defered.resolve(res);
                });
                return defered.promise;
            };
            service.activeUser = function (params) {
                var defered = $q.defer();
                var path = [baseConfig.apiUrl, 'help', 'help_page', 'active_user'].join('/');
                $http.post(path, params, httpBlockConfig).success(function(res) {
                    defered.resolve(res);
                });
                return defered.promise;
            };
            service.disactiveUser = function (params) {
                var defered = $q.defer();
                var path = [baseConfig.apiUrl, 'help', 'help_page', 'disactive_user'].join('/');
                $http.post(path, params, httpBlockConfig).success(function(res) {
                    defered.resolve(res);
                });
                return defered.promise;
            };
            service.deleteUser = function (params) {
                var defered = $q.defer();
                var path = [baseConfig.apiUrl, 'help', 'help_page', 'delete_user'].join('/');
                $http.post(path, params, httpBlockConfig).success(function(res) {
                    defered.resolve(res);
                });
                return defered.promise;
            };
            
            return service;
        }]);
    
    module.directive('scroll', ['$window', '$rootScope', '$stateParams', function ($window, $rootScope, $stateParams) {
        return function (scope, element, attrs) {
            ngElement($window).bind("scroll", function () {
                var scrollHeight = $(document).height() - $(window).height();
                var percent = this.pageYOffset * 100 / scrollHeight;
                if (percent >= 90 && !$rootScope.isBlocked) {
                    if ($stateParams.menuId == 'feedback' && $rootScope.pagination.curPage < $rootScope.pagination.maxPage) {
                        $rootScope.isBlocked = true;
                        scope.loadFeedback();
                    }
                }
                scope.$apply();
            });
        };
    }]);
})();