
Vagrant.configure("2") do |config|
    config.vm.box = "ubuntu/xenial64"
    config.vm.define "armbreaker"
    config.vm.box_check_update = false
    config.vm.network "forwarded_port", guest: 80, host: 8080, host_ip: "127.0.0.1"
    config.vm.network "private_network", ip: "192.168.33.10"
    config.vm.provision :shell, path: "vagrantFiles/vagrantBootstrap.sh"
end
