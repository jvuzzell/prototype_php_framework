source ~/.bash_profile
sites_dir=~/sites
protools_framework_dir=$sites_dir/_prototypes/protools_framework
setup_files=$sites_dir/_prototypes/tmp/setup_files/

rm -r $protools_framework_dir/tests/active_tests
rm -r $protools_framework_dir/tests/archive
rm -r $protools_framework_dir/tests/reports
rm -r $protools_framework_dir/tests/test_results

cp -R $setup_files/tests/active_tests $protools_framework_dir/tests/active_tests
cp -R $setup_files/tests/archive $protools_framework_dir/tests/archive
cp -R $setup_files/tests/reports $protools_framework_dir/tests/reports
cp -R $setup_files/tests/test_results  $protools_framework_dir/tests/test_results 