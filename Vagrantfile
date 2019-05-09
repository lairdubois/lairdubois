Vagrant.configure(2) do |config|

  # Debian box
  config.vm.box = "debian/stretch64"
  config.vm.define "ladb-devbox"

  # Sync current directory inside the VM, using the default
  config.vm.synced_folder ".", "/vagrant"

  # Specify SSH port
  config.vm.network :forwarded_port, guest: 22, host: 3322, id: "ssh"
  config.vm.network :forwarded_port, guest: 80, host: 3380, id: "http"
  config.vm.network :forwarded_port, guest: 3443, host: 3443, id: "https"

  config.vm.provider "virtualbox" do |vb|
    vb.memory = "4096"
    vb.cpus = 4
  end

  # Run the Ansible playbook to configure this VM for development
  config.vm.provision "ansible" do |ansible|
    ansible.playbook = "../lairdubois-ansible/lairdubois.yml"
    ansible.verbose = "v"
    ansible.limit = "all"
    ansible.inventory_path= "../lairdubois-ansible/environments/dev"
    # ansible.vault_password_file = "~/Private/ansible/lairdubois"
    ansible.extra_vars = {
      # import_db_file: "~/backup-ladb.sql.gz"
    }
  end

end