$(function() {
    showHash(location.hash);
    window.onhashchange = function() {
        showHash(location.hash);
    };
});
function showHash(hash) {
    $('.box > div').hide();
    var target = hash == "" ? 1 : hash.charAt(hash.length - 1);
    $('.box > div').eq(target - 1).show();
    target != 1 && (location.hash = '#' + target);
}
function save() {
    $('#save').attr("disabled",true);
    $.ajax({
        type: 'post',
        url: '/install/lib/base.php?act=install',
        data: {
            dbhost:$('#dbhost').val(),
            dbname:$('#dbname').val(),
            dbuser:$('#dbuser').val(),
            dbpwd:$('#dbpwd').val(),
        },
        dataType: "json",
        success: function(resule){
            $('#save').attr("disabled",false);
            if(resule.state=='success'){
                showHash('#4');
            }else{
                alert(resule.info);
                $('#save').attr("disabled",false);
            }
        },
        error: function(resule) {
            console.log(resule);
            alert('[ Error ] 请求处理失败,错误信息已输出控制台!');
            $('#save').attr("disabled",false);
        },
    });
}