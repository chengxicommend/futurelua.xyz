<?php


$app->get('/', 'App\Http\HomeController')->setName('home');
$app->get('/tos', 'App\Http\TOSController')->setName('tos');
$app->get('/banned', 'App\Http\BanController')->setName('banned');
$app->get('/logout', 'App\Http\HomeController:logout')->setName("logout");

$app->post('/login', 'App\Http\SigninController:post')->setName("signin");;
$app->post('/register', 'App\Http\RegisterController:post')->setName("register");;

$app->get('/user/edit/hwid/reset/{id}', 'App\Http\Panel\UserController:resetHWID')->setName('panel.user.reset.hwid');
$app->get('/switch-language', 'App\Http\HomeController:SwitchLanguage')->setName('switch-language');

$app->group('/api', function () use ($container) {
    $this->post('/authentication', 'App\Http\Api\Authenticate');
    // PRODUCTS
    $this->post('/update', 'App\Http\Api\UpdateController');

    //Product List
    $this->post('/user/products', 'App\Http\Api\ProductController:getProducts');
    //Get Product
    $this->post('/product/{id}', 'App\Http\Api\ProductController:getProduct');
    //Get Config
    $this->post('/config/{id}', 'App\Http\Api\ConfigController:getProduct');

    //Get Config
    $this->post('/geninvite', 'App\Http\Api\GenInvite');

    //Helper
    $this->post('/helper/locations', 'App\Http\Api\HelperController:locations');
    $this->get('/helper/location/{id}', 'App\Http\Api\HelperController:getLocation');
});

$app->group('/panel', function () use ($container) {
    $this->get('', 'App\Http\Panel\HomeController')->setName('panel');
    $this->get('/changeEmail', 'App\Http\Panel\HomeController:changeEmail')->setName('panel.user.change.email');


    $this->get('/users', 'App\Http\Panel\UserController')->setName('panel.users');
    $this->get('/download', 'App\Http\Panel\DownloadController')->setName('panel.download');
    $this->get('/update', 'App\Http\Panel\UpdateController')->setName('panel.update');
    $this->post('/update', 'App\Http\Panel\UpdateController:post');
    $this->get('/invitations', 'App\Http\Panel\InvitationController')->setName('panel.invitations');

    $this->get('/invitation/create', 'App\Http\Panel\InvitationController:createInvitationGet')->setName('panel.invitation.create');
    $this->post('/invitation/create', 'App\Http\Panel\InvitationController:createInvitationPost');


    $this->get('/groups', 'App\Http\Panel\GroupController')->setName('panel.groups');
    $this->get('/group/create', 'App\Http\Panel\GroupController:createGet')->setName('panel.group.create');
    $this->post('/group/create', 'App\Http\Panel\GroupController:createPost');
    $this->get('/group/delete/{id}', 'App\Http\Panel\GroupController:delete')->setName('panel.group.delete');
    $this->get('/group/admins/{id}', 'App\Http\Panel\GroupController:manageAdminsGet')->setName('panel.group.admins');
    $this->get('/group/admins/add/{id}', 'App\Http\Panel\GroupController:addAdmin')->setName('panel.group.admins.add');
    $this->get('/group/reseller/add/{id}', 'App\Http\Panel\GroupController:addReseller')->setName('panel.group.reseller.add');
    $this->get('/group/admins/user/add/{id}', 'App\Http\Panel\GroupController:addUser')->setName('panel.group.user.add');
    $this->get('/group/reseller/user/add/{id}', 'App\Http\Panel\GroupController:addResellerUser')->setName('panel.reseller.user.add');
    $this->get('/user/group/admins/delete/{id}/{userid}', 'App\Http\Panel\GroupController:removeAdmin')->setName('panel.group.admins.delete');
    $this->get('/user/group/reseller/delete/{id}/{userid}', 'App\Http\Panel\GroupController:removeReseller')->setName('panel.group.resellers.delete');
    $this->get('/user/group/user/delete/{id}/{userid}', 'App\Http\Panel\GroupController:removeUsers')->setName('panel.group.users.delete');

    $this->get('/user/group/add/{id}', 'App\Http\Panel\UserController:addGroup')->setName('panel.user.group.add');
    $this->get('/user/group/delete/{id}/{userid}', 'App\Http\Panel\UserController:removeGroup')->setName('panel.user.group.delete');


    $this->get('/user/edit/{id}', 'App\Http\Panel\UserController:editUserGet')->setName('panel.user.edit');
    $this->post('/user/edit/{id}', 'App\Http\Panel\UserController:editUserPost');


    $this->get('/products', 'App\Http\Panel\ProductController')->setName('panel.products');
    $this->get('/product/create', 'App\Http\Panel\ProductController:createProductGet')->setName('panel.product.create');
    $this->post('/product/create', 'App\Http\Panel\ProductController:createProductPost');
    $this->get('/product/edit/{id}', 'App\Http\Panel\ProductController:editProductGet')->setName('panel.product.edit');
    $this->post('/product/edit/{id}', 'App\Http\Panel\ProductController:editProductPost');
    $this->get('/product/delete/{id}', 'App\Http\Panel\ProductController:delete')->setName('panel.product.delete');


    $this->get('/configs', 'App\Http\Panel\ConfigController')->setName('panel.configs');
    $this->get('/configs/create', 'App\Http\Panel\ConfigController:createProductGet')->setName('panel.config.create');
    $this->post('/configs/create', 'App\Http\Panel\ConfigController:createProductPost');
    $this->get('/configs/edit/{id}', 'App\Http\Panel\ConfigController:editProductGet')->setName('panel.config.edit');
    $this->post('/configs/edit/{id}', 'App\Http\Panel\ConfigController:editProductPost');
    $this->get('/configs/delete/{id}', 'App\Http\Panel\ConfigController:delete')->setName('panel.config.delete');
    $this->get('/configs/products/{id}', 'App\Http\Panel\ConfigController:configLuasGet')->setName('panel.config.products');
    $this->get('/configs/products/add/{id}', 'App\Http\Panel\ConfigController:configLuasAdd')->setName('panel.config.products.add');
    $this->get('/configs/products/delete/{id}/{productid}', 'App\Http\Panel\ConfigController:configLuasDelete')->setName('panel.config.products.delete');


    $this->get('/helpers', 'App\Http\Panel\HelperController')->setName('panel.helper');
    $this->get('/helpers/create', 'App\Http\Panel\HelperController:createGet')->setName('panel.helper.create');
    $this->post('/helpers/create', 'App\Http\Panel\HelperController:createPost');
    $this->get('/helpers/edit/{id}', 'App\Http\Panel\HelperController:editGet')->setName('panel.helper.edit');
    $this->post('/helpers/edit/{id}', 'App\Http\Panel\HelperController:editPost');
    $this->get('/helpers/delete/{id}', 'App\Http\Panel\HelperController:delete')->setName('panel.helper.delete');


    $this->get('/reseller/groups/{id}', 'App\Http\Panel\ResellerController:manageUserGet')->setName('panel.reseller.user');


    $this->get('/reseller/groups', 'App\Http\Panel\ResellerController:manageGroupsGet')->setName('panel.reseller.groups');
    $this->get('/reseller/manage', 'App\Http\Panel\ResellerController:manageReseller')->setName('panel.reseller');
    $this->post('/reseller/manage', 'App\Http\Panel\ResellerController:aadResellerPost');
    $this->get('/reseller/users', 'App\Http\Panel\ResellerController')->setName('panel.reseller.users');
    $this->post('/reseller/users', 'App\Http\Panel\ResellerController:addUserPost');

})->add(new App\Http\Middleware\Authenticated($container));
