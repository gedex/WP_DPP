# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|
  config.vm.box = "precise"
  config.vm.box_url = "http://files.vagrantup.com/precise32.box"

  config.ssh.forward_agent = true

  config.vm.hostname = "wp-dpp"
  config.vm.network :private_network, ip: "33.33.33.33"

  # Provisioning
  config.vm.provision :shell, :path => "provision.sh"
end
