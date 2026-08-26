namespace TodoList.Api.Models
{
    public class TodoItem
    {
        public Guid Id { get; set; }
        public string Title { get; set; } = string.Empty;
        public bool IsCompleted { get; set; }
        public Guid TodoListItemId { get; set; }
        public TodoListItem? TodoList { get; set; }
    }
}
