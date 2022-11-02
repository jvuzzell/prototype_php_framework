source ~/.bash_profile
sites_dir=~/sites
protools_framework_dir=_prototypes/protools_framework

if [ ! -d "$sites_dir" ];then
    echo "Missing directory; Path - $sites_dir"
    kill -INT $$
fi 

ramp.addhost prototypes $protools_framework_dir/bootstrap/sites/
ramp.addhost testing.prototypes $protools_framework_dir/bootstrap/testing/
ramp.addhost api.prototypes $protools_framework_dir/bootstrap/api_gateway/
ramp.addhost assets.prototypes $protools_framework_dir/bootstrap/assets/
ramp.addhost builder.prototypes $protools_framework_dir/bootstrap/sites/
ramp.addhost cms.prototypes $protools_framework_dir/bootstrap/sites/
ramp.hosts

mkdir $sites_dir/$protools_framework_dir/portfolio/sites/builder.prototypes
mkdir $sites_dir/$protools_framework_dir/portfolio/sites/cms.prototypes
mkdir $sites_dir/$protools_framework_dir/portfolio/sites/prototypes

#ramp.addhost docs.prototypes
#ramp.addhost login.prototypes 
