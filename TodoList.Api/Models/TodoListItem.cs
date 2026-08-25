namespace TodoList.Api.Models
{
    public class TodoListItem
    {
        public Guid Id { get; set; }
        public string Title { get; set; } = string.Empty;
        public List<TodoItem> Todos { get; set; } = new List<TodoItem>();
    }
}
