@servers(['web' => 'user@192.168.1.1'])

@task('deploy',['on' => 'web'])
cd /var/www
git pull origin main
@endtask
