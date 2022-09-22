source ~/.bash_profile
sites_dir=~/sites
protools_framework_dir=$sites_dir/_prototypes/protools_framework

if [ ! -d "$sites_dir" ];then
    echo "Missing directory; Path - $sites_dir"
    kill -INT $$
fi 

ramp.removehost prototypes $protools_framework_dir/bootstrap/sites/
ramp.removehost testing.prototypes $protools_framework_dir/bootstrap/testing/
ramp.removehost api.prototypes $protools_framework_dir/bootstrap/api-gateway/
ramp.removehost assets.prototypes $protools_framework_dir/bootstrap/assets/
ramp.removehost builder.prototypes $protools_framework_dir/bootstrap/sites/
ramp.removehost cms.prototypes $protools_framework_dir/bootstrap/sites/
ramp.hosts
